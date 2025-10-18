<?php
// api/clock_in.php
header('Content-Type: application/json');
require_once '../db_config.php'; // DB接続情報

// POSTデータ取得
$data = json_decode(file_get_contents('php://input'), true);
$userId = isset($data['userId']) ? intval($data['userId']) : 0;
$datetime = isset($data['datetime']) ? $data['datetime'] : '';
$manual = isset($data['manual']) ? (int)(bool)$data['manual'] : 0;

if (!$userId || !$datetime) {
    http_response_code(400);
    echo json_encode(['message' => 'パラメータが不足しています']);
    exit;
}

$workDate = substr($datetime, 0, 10);

try {
    // 既存レコード確認
    $stmt = $pdo->prepare('SELECT * FROM timecards WHERE user_id = ? AND work_date = ?');
    $stmt->execute([$userId, $workDate]);
    $timecard = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($timecard && $timecard['clock_in']) {
        http_response_code(400);
        echo json_encode(['message' => 'すでに出勤打刻済みです']);
        exit;
    }
    if ($timecard) {
        $stmt = $pdo->prepare('UPDATE timecards SET clock_in = ?, clock_in_manual = ? WHERE id = ?');
        $stmt->execute([$datetime, $manual, $timecard['id']]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO timecards (user_id, work_date, clock_in, clock_in_manual) VALUES (?, ?, ?, ?)');
        $stmt->execute([$userId, $workDate, $datetime, $manual]);
    }
    echo json_encode(['message' => '出勤打刻を記録しました']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['message' => 'DBエラー', 'error' => $e->getMessage()]);
}
