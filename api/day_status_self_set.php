<?php
// api/day_status_self_set.php
// 自分自身の日ステータスを設定（上書き）する（一般ユーザー可）
session_start();
header('Content-Type: application/json');
require_once '../db_config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'not logged in']);
    exit;
}
$userId = intval($_SESSION['user_id']);

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$date = isset($input['date']) ? $input['date'] : null;
$status = isset($input['status']) ? $input['status'] : null; // off_full/off_am/off_pm/ignore/working
$note = isset($input['note']) ? trim(strval($input['note'])) : null;

if (!$date || !$status || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid input']);
    exit;
}

// 当日のみ許可（誤操作や遡及防止）
// 期間ロック中は変更不可
function is_locked($pdo, $userId, $date) {
    $q = $pdo->prepare('SELECT 1 FROM attendance_period_locks WHERE status="locked" AND (user_id IS NULL OR user_id=?) AND start_date <= ? AND end_date >= ? LIMIT 1');
    $q->execute([$userId, $date, $date]);
    return (bool)$q->fetchColumn();
}
if (is_locked($pdo, $userId, $date)) {
    http_response_code(403);
    echo json_encode(['error' => 'locked period']);
    exit;
}

// 雇用区分取得（常勤:1 / パート:0）。user_detail が無ければ常勤扱い
$ftStmt = $pdo->prepare('SELECT COALESCE(full_time,1) AS full_time FROM user_detail WHERE user_id = ?');
$ftStmt->execute([$userId]);
$fullTimeRow = $ftStmt->fetch(PDO::FETCH_ASSOC);
$isFullTime = $fullTimeRow ? (int)$fullTimeRow['full_time'] === 1 : true;

$allowed = ['off_full', 'off_am', 'off_pm', 'working', 'ignore'];
if (!in_array($status, $allowed, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid status']);
    exit;
}
// パート（full_time=0）は半休（off_am/off_pm）禁止
if (!$isFullTime && ($status === 'off_am' || $status === 'off_pm')) {
    http_response_code(403);
    echo json_encode(['error' => 'half-day not allowed for part-time']);
    exit;
}

try {
    $pdo->beginTransaction();
    // 既存の当日上書きを無効化
    $stmt = $pdo->prepare('UPDATE day_status_overrides SET revoked_at = NOW() WHERE user_id = ? AND date = ? AND revoked_at IS NULL');
    $stmt->execute([$userId, $date]);

    if ($status !== 'working') {
        // 全休へ変更する場合は既存の当日勤務記録(timecards + breaks)を削除し、UI を一貫して「非勤務」扱いにする
        if ($status === 'off_full') {
            try {
                // 当日 timecards を取得（ロック）
                $tcSel = $pdo->prepare('SELECT id FROM timecards WHERE user_id = ? AND work_date = ? FOR UPDATE');
                $tcSel->execute([$userId, $date]);
                $toDelete = $tcSel->fetchAll(PDO::FETCH_ASSOC);
                foreach ($toDelete as $tc) {
                    $tid = (int)$tc['id'];
                    // 休憩削除
                    $pdo->prepare('DELETE FROM breaks WHERE timecard_id = ?')->execute([$tid]);
                    // 本体削除
                    $pdo->prepare('DELETE FROM timecards WHERE id = ?')->execute([$tid]);
                }
            } catch (Exception $ex) {
                // 削除失敗は致命的でない（監査用残存を許容）→ ロールバックせず継続
            }
        }
        // working は既定に戻すだけ＝新規挿入しない
        $stmt = $pdo->prepare('INSERT INTO day_status_overrides (user_id, date, status, note, created_by) VALUES (?,?,?,?,?)');
        $stmt->execute([$userId, $date, $status, $note, $userId]);
    }

    $pdo->commit();
    echo json_encode(['ok' => true, 'status' => $status]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'server error']);
}
