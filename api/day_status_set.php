<?php
/*
 * 目的: 指定日のステータスを設定します（管理操作）。
 * 入力: 日付、ユーザー、ステータス種別
 * 出力: 成功/失敗（更新結果）
 */
?>
<?php
/*
 * 目的: 指定日の日ステータスを設定します（管理者/本人範囲）。
 * 入力: date, status（work/off/paid/ignore）
 * 出力: 設定結果
 */
?>
<?php
// api/day_status_set.php
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
$status = isset($input['status']) ? $input['status'] : null;
$note = isset($input['note']) ? trim(strval($input['note'])) : null;

if (!$userId || !$date || !$status || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
  http_response_code(400);
  echo json_encode(['error' => 'invalid input']);
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
  // revoke existing active overrides for this user/date
  $stmt = $pdo->prepare('UPDATE day_status_overrides SET revoked_at = NOW() WHERE user_id = ? AND date = ? AND revoked_at IS NULL');
  $stmt->execute([$userId, $date]);
  // insert new override
  $stmt = $pdo->prepare('INSERT INTO day_status_overrides (user_id, date, status, note, created_by) VALUES (?,?,?,?,?)');
  $stmt->execute([$userId, $date, $status, $note, $adminId]);
  $pdo->commit();
  echo json_encode(['ok' => true]);
} catch (Exception $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  http_response_code(500);
  echo json_encode(['error' => 'server error']);
}
