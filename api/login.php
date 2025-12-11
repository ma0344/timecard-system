<?php
/*
 * 目的: ユーザーログインを処理します（セッション開始）。
 * 入力: メール/ユーザーID、パスワード
 * 出力: 成功/失敗（セッション確立、ユーザー情報）
 */
?>
<?php
/*
 * 目的: 認証処理（ログイン）を実施します。
 * 入力: email/username、password
 * 出力: セッション開始、ユーザー情報
 */
?>
<?php
// api/login.php
session_start();
header('Content-Type: application/json');
require_once '../db_config.php';

$data = json_decode(file_get_contents('php://input'), true);
$username = isset($data['username']) ? trim($data['username']) : '';
$password = isset($data['password']) ? $data['password'] : '';

if (!$username || !$password) {
  http_response_code(400);
  echo json_encode(['message' => 'ユーザー名とパスワードは必須です']);
  exit;
}

try {
  $stmt = $pdo->prepare('SELECT id, password_hash, must_reset_password FROM users WHERE name = ? AND visible = 1');
  $stmt->execute([$username]);
  $user = $stmt->fetch(PDO::FETCH_ASSOC);
  if ($user && password_verify($password, $user['password_hash'])) {
    $_SESSION['user_id'] = $user['id'];
    echo json_encode(['message' => 'ログイン成功', 'must_reset_password' => $user['must_reset_password']]);
  } else {
    http_response_code(401);
    echo json_encode(['message' => 'ユーザー名またはパスワードが正しくありません']);
  }
} catch (PDOException $e) {
  http_response_code(500);
  echo json_encode([
    'message' => 'DBエラー',
    'error' => $e->getMessage()
  ]);
}
