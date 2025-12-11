<?php
/*
 * 目的: 休憩開始を記録します。
 * 入力: userId、use_server_time 等
 * 出力: 記録結果（成功/失敗）
 */
?>
<?php
// api/break_start.php
header('Content-Type: application/json');
require_once '../db_config.php';

$data = json_decode(file_get_contents('php://input'), true);
$userId = isset($data['userId']) ? intval($data['userId']) : 0;
$useServerTime = isset($data['use_server_time']) ? (bool)$data['use_server_time'] : false;
$datetime = isset($data['datetime']) ? $data['datetime'] : '';
$manual = isset($data['manual']) ? (int)(bool)$data['manual'] : 0;
if ($useServerTime) {
  $datetime = date('Y-m-d H:i:s');
  $manual = 0;
}

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
  $stmt = $pdo->prepare('SELECT * FROM breaks WHERE timecard_id = ? AND break_end IS NULL ORDER BY break_start DESC LIMIT 1');
  $stmt->execute([$timecard['id']]);
  $lastBreak = $stmt->fetch(PDO::FETCH_ASSOC);
  if ($lastBreak) {
    http_response_code(400);
    echo json_encode(['message' => '未終了の休憩があります']);
    exit;
  }
  $stmt = $pdo->prepare('INSERT INTO breaks (timecard_id, break_start, break_start_manual) VALUES (?, ?, ?)');
  $stmt->execute([$timecard['id'], $datetime, $manual]);
  echo json_encode(['message' => '休憩開始を記録しました']);
} catch (PDOException $e) {
  http_response_code(500);
  echo json_encode(['message' => 'DBエラー: ' . $e->getMessage(), 'error' => $e->getMessage()]);
}
