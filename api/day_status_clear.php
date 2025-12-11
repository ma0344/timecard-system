<?php
/*
 * 目的: 指定日のステータスをクリアします。
 * 入力: 日付、ユーザー識別
 * 出力: 成功/失敗（更新件数）
 */
?>
<?php
/*
 * 目的: 指定日のステータス設定をクリアします。
 * 入力: date
 * 出力: クリア結果
 */
?>
<?php
// api/day_status_clear.php
session_start();
header('Content-Type: application/json');
require_once '../db_config.php';

// Admin check
if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(['error' => 'not logged in']);
  exit;
}
$adminId = $_SESSION['user_id'];
$stmt = $pdo->prepare('SELECT role FROM users WHERE id = ?');
$stmt->execute([$adminId]);
$me = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$me || $me['role'] !== 'admin') {
  http_response_code(403);
  echo json_encode(['error' => 'forbidden']);
  exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$userId = isset($input['user_id']) ? intval($input['user_id']) : 0;
$date = isset($input['date']) ? $input['date'] : null;
if (!$userId || !$date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
  http_response_code(400);
  echo json_encode(['error' => 'invalid input']);
  exit;
}

try {
  $stmt = $pdo->prepare('UPDATE day_status_overrides SET revoked_at = NOW() WHERE user_id = ? AND date = ? AND revoked_at IS NULL');
  $stmt->execute([$userId, $date]);
  echo json_encode(['ok' => true]);
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(['error' => 'server error']);
}
