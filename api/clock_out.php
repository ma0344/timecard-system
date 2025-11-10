<?php
// api/clock_out.php
header('Content-Type: application/json');
require_once '../db_config.php';

$data = json_decode(file_get_contents('php://input'), true);
$userId = isset($data['userId']) ? intval($data['userId']) : 0;
$useServerTime = isset($data['use_server_time']) ? (bool)$data['use_server_time'] : false;
$datetime = isset($data['datetime']) ? $data['datetime'] : '';
$manual = isset($data['manual']) ? (int)(bool)$data['manual'] : 0;
$vehicleDistance = isset($data['vehicle_distance']) ? intval($data['vehicle_distance']) : null;
// 退勤時メモ（任意・最大1000文字）
$memoText = isset($data['memo_text']) ? (string)$data['memo_text'] : null;
if ($memoText !== null) {
    // 長さ制限と軽い正規化（制御文字の除去）
    $memoText = mb_substr($memoText, 0, 1000);
    // 制御文字の除去（改行は許容）
    $memoText = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $memoText);
    // utf8mb3対策: サロゲートペア/4バイト文字(絵文字等)を除去
    $memoText = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $memoText);
}

if ($useServerTime) {
    $datetime = date('Y-m-d H:i:s');
    $manual = 0;
}

if (!$userId || !$datetime) {
    http_response_code(400);
    echo json_encode(['message' => 'パラメータが不足しています']);
    exit;
}

// work_date はサーバー日付基準
$workDate = $useServerTime ? date('Y-m-d') : substr($datetime, 0, 10);

try {
    $stmt = $pdo->prepare('SELECT * FROM timecards WHERE user_id = ? AND work_date = ?');
    $stmt->execute([$userId, $workDate]);
    $timecard = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$timecard || !$timecard['clock_in']) {
        http_response_code(400);
        echo json_encode(['message' => '出勤打刻がありません']);
        exit;
    }
    if ($timecard['clock_out']) {
        http_response_code(400);
        echo json_encode(['message' => 'すでに退勤打刻済みです']);
        exit;
    }
    // 退勤時に未終了の休憩があれば処理
    $stmt = $pdo->prepare('SELECT * FROM breaks WHERE timecard_id = ? AND break_end IS NULL ORDER BY break_start DESC LIMIT 1');
    $stmt->execute([$timecard['id']]);
    $lastBreak = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($lastBreak) {
        // clock_outを休憩開始時刻で更新
        $stmt = $pdo->prepare('UPDATE timecards SET clock_out = ?, clock_out_manual = ?' . ($vehicleDistance !== null ? ', vehicle_distance = ?' : '') . ' WHERE id = ?');
        $params = [$lastBreak['break_start'], $manual];
        if ($vehicleDistance !== null) $params[] = $vehicleDistance;
        $params[] = $timecard['id'];
        $stmt->execute($params);
        // break_startとclock_outが同じなら休憩レコードを削除
        if ($lastBreak['break_start'] === $lastBreak['break_start']) {
            $stmt = $pdo->prepare('DELETE FROM breaks WHERE id = ?');
            $stmt->execute([$lastBreak['id']]);
        }
        // メモ保存（失敗しても退勤は成功扱い）
        if ($memoText !== null && $memoText !== '') {
            try {
                $stmt = $pdo->prepare('INSERT INTO user_day_memos (user_id, work_date, memo_text) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE memo_text = ?, updated_at = CURRENT_TIMESTAMP');
                $stmt->execute([$userId, $workDate, $memoText, $memoText]);
            } catch (PDOException $e) { /* ignore memo save error */
            }
        }
        echo json_encode(['message' => '退勤打刻を記録しました（休憩中に退勤）']);
        exit;
    }
    $stmt = $pdo->prepare('UPDATE timecards SET clock_out = ?, clock_out_manual = ?' . ($vehicleDistance !== null ? ', vehicle_distance = ?' : '') . ' WHERE id = ?');
    $params = [$datetime, $manual];
    if ($vehicleDistance !== null) $params[] = $vehicleDistance;
    $params[] = $timecard['id'];
    $stmt->execute($params);
    // メモ保存（失敗しても退勤は成功扱い）
    if ($memoText !== null && $memoText !== '') {
        try {
            $stmt = $pdo->prepare('INSERT INTO user_day_memos (user_id, work_date, memo_text) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE memo_text = ?, updated_at = CURRENT_TIMESTAMP');
            $stmt->execute([$userId, $workDate, $memoText, $memoText]);
        } catch (PDOException $e) { /* ignore memo save error */
        }
    }
    echo json_encode(['message' => '退勤打刻を記録しました']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['message' => 'DBエラー: ' . $e->getMessage(), 'error' => $e->getMessage()]);
}
