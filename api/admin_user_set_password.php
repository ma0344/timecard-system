<?php
/*
 * 目的: 管理者が対象ユーザーのパスワードを再設定します。
 * 入力: user_id、新パスワード
 * 出力: 設定結果
 */
?>
<?php
// api/admin_user_set_password.php
session_start();
header('Content-Type: application/json');
require_once '../db_config.php';

if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(['error' => 'not logged in']);
  exit;
}
$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare('SELECT role FROM users WHERE id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user || $user['role'] !== 'admin') {
  http_response_code(403);
  echo json_encode(['error' => 'forbidden']);
  exit;
}
// POSTでユーザーIDと新パスワードを受け取る
$data = json_decode(file_get_contents('php://input'), true);
$targetId = $data['id'] ?? null;
$newPassword = $data['password'] ?? null;
if (!$targetId || !$newPassword) {
  http_response_code(400);
  echo json_encode(['error' => 'id and password required']);
  exit;
}
$hash = password_hash($newPassword, PASSWORD_DEFAULT);
$stmt = $pdo->prepare('UPDATE users SET password_hash = ?, must_reset_password = 1 WHERE id = ?');
$stmt->execute([$hash, $targetId]);
echo json_encode(['success' => true]);
