<?php
/*
 * 目的: SMTP送信設定を保存します。
 * 入力: SMTP設定（ホスト、ポート、認証、差出人 等）
 * 出力: 成功/失敗（保存結果メッセージ）
 */
?>
<?php
// api/smtp_settings_save.php
// SMTP設定の保存（app_settingsテーブルにJSONとして保存）
session_start();
header('Content-Type: application/json');
require_once '../db_config.php';

// 管理者のみ
if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(['error' => 'not logged in']);
  exit;
}
$adminId = $_SESSION['user_id'];
$stmt = $pdo->prepare('SELECT role FROM users WHERE id = ?');
$stmt->execute([$adminId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row || $row['role'] !== 'admin') {
  http_response_code(403);
  echo json_encode(['error' => 'forbidden']);
  exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) {
  http_response_code(400);
  echo json_encode(['error' => 'invalid input']);
  exit;
}

$host = trim((string)($data['host'] ?? ''));
$port = (int)($data['port'] ?? 587);
$secure = (string)($data['secure'] ?? 'tls');
$username = (string)($data['username'] ?? '');
$password = (string)($data['password'] ?? '');
$from_email = trim((string)($data['from_email'] ?? ''));
$from_name = trim((string)($data['from_name'] ?? ''));

if ($port <= 0 || $port > 65535) {
  http_response_code(400);
  echo json_encode(['error' => 'invalid port']);
  exit;
}
if (!in_array($secure, ['none', 'ssl', 'tls'], true)) {
  http_response_code(400);
  echo json_encode(['error' => 'invalid secure']);
  exit;
}
if ($from_email !== '' && !filter_var($from_email, FILTER_VALIDATE_EMAIL)) {
  http_response_code(400);
  echo json_encode(['error' => 'invalid from_email']);
  exit;
}

$payload = json_encode([
  'host' => $host,
  'port' => $port,
  'secure' => $secure,
  'username' => $username,
  'password' => $password,
  'from_email' => $from_email,
  'from_name' => $from_name
], JSON_UNESCAPED_UNICODE);

try {
  $pdo->exec('CREATE TABLE IF NOT EXISTS app_settings (
        `key` VARCHAR(191) PRIMARY KEY,
        `value` TEXT,
        `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

  $stmt = $pdo->prepare('INSERT INTO app_settings(`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)');
  $stmt->execute(['smtp', $payload]);
  echo json_encode(['ok' => true]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error' => 'db error']);
}
