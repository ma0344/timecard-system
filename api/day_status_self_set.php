<?php
// api/day_status_self_set.php
// 自分自身の当日の日ステータスを設定（上書き）する（一般ユーザー可）
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
$today = date('Y-m-d');
if ($date !== $today) {
    http_response_code(403);
    echo json_encode(['error' => 'only today allowed']);
    exit;
}

$allowed = ['off_full', 'off_am', 'off_pm', 'working', 'ignore'];
if (!in_array($status, $allowed, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid status']);
    exit;
}

try {
    $pdo->beginTransaction();
    // 既存の当日上書きを無効化
    $stmt = $pdo->prepare('UPDATE day_status_overrides SET revoked_at = NOW() WHERE user_id = ? AND date = ? AND revoked_at IS NULL');
    $stmt->execute([$userId, $date]);

    if ($status !== 'working') {
        // working は既定に戻すだけ＝新規挿入しない
        $stmt = $pdo->prepare('INSERT INTO day_status_overrides (user_id, date, status, note, created_by) VALUES (?,?,?,?,?)');
        $stmt->execute([$userId, $date, $status, $note, $userId]);
    }

    $pdo->commit();
    echo json_encode(['ok' => true]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'server error']);
}
