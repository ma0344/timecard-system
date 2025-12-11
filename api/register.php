<?php
/*
 * 目的: 新規ユーザー登録を受け付けます。
 * 入力: ユーザー情報（氏名、メール、パスワード 等）
 * 出力: 成功/失敗（作成されたユーザーIDなど）
 */
?>
<?php
// api/register.php
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
  // ユーザー名重複チェック
  $stmt = $pdo->prepare('SELECT id FROM users WHERE name = ?');
  $stmt->execute([$username]);
  if ($stmt->fetch()) {
    http_response_code(400);
    echo json_encode(['message' => 'このユーザー名は既に使われています']);
    exit;
  }
  // パスワードハッシュ化
  $hash = password_hash($password, PASSWORD_DEFAULT);
  $stmt = $pdo->prepare('INSERT INTO users (name, password_hash, role, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())');
  $stmt->execute([$username, $hash, 'user']);
  echo json_encode(['message' => 'ユーザー登録が完了しました']);
} catch (PDOException $e) {
  http_response_code(500);
  // エラー内容をHTMLとしても出力（デバッグ用）
  echo json_encode([
    'message' => 'DBエラー',
    'error' => $e->getMessage()
  ]);
  echo '<pre style="color:red;">DBエラー: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</pre>';
}
