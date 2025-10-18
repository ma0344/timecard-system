<?php
// api/clock_out.php
header('Content-Type: application/json');
require_once '../db_config.php';

$data = json_decode(file_get_contents('php://input'), true);
$userId = isset($data['userId']) ? intval($data['userId']) : 0;
$datetime = isset($data['datetime']) ? $data['datetime'] : '';
$manual = isset($data['manual']) ? (int)(bool)$data['manual'] : 0;
$vehicleDistance = isset($data['vehicle_distance']) ? intval($data['vehicle_distance']) : null;

if (!$userId || !$datetime) {
    http_response_code(400);
    echo json_encode(['message' => 'パラメータが不足しています']);
    exit;
}

$workDate = substr($datetime, 0, 10);

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
        echo json_encode(['message' => '退勤打刻を記録しました（休憩中に退勤）']);
        exit;
    }
    $stmt = $pdo->prepare('UPDATE timecards SET clock_out = ?, clock_out_manual = ?' . ($vehicleDistance !== null ? ', vehicle_distance = ?' : '') . ' WHERE id = ?');
    $params = [$datetime, $manual];
    if ($vehicleDistance !== null) $params[] = $vehicleDistance;
    $params[] = $timecard['id'];
    $stmt->execute($params);
    echo json_encode(['message' => '退勤打刻を記録しました']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['message' => 'DBエラー: ' . $e->getMessage(), 'error' => $e->getMessage()]);
}
