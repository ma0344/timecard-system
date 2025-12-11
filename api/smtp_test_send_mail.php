<?php
/*
 * 目的: 設定済みSMTPでテストメールを送信します（宛先指定版）。
 * 入力: 宛先メールアドレス、件名/本文（任意）
 * 出力: 成功/失敗（送信ログやメッセージ）
 */
?>
<?php
// api/smtp_test_send_mail.php
// 保存済みのSMTP設定でテストメールを1通送る（プレーンテキスト）
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

// 入力: to_email（必須、1件）
$data = json_decode(file_get_contents('php://input'), true);
$to = isset($data['to_email']) ? trim((string)$data['to_email']) : '';
if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
  http_response_code(400);
  echo json_encode(['error' => 'invalid to_email']);
  exit;
}

// 設定読み込み（リクエストに config があれば優先、なければ保存値）
try {
  $pdo->exec('CREATE TABLE IF NOT EXISTS app_settings (
        `key` VARCHAR(191) PRIMARY KEY,
        `value` TEXT,
        `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
  if (isset($data['config']) && is_array($data['config'])) {
    $cfg = $data['config'];
  } else {
    $stmt = $pdo->prepare('SELECT value FROM app_settings WHERE `key` = ? LIMIT 1');
    $stmt->execute(['smtp']);
    $val = $stmt->fetchColumn();
    if (!$val) {
      http_response_code(400);
      echo json_encode(['error' => 'smtp settings not found']);
      exit;
    }
    $cfg = json_decode($val, true);
    if (!is_array($cfg)) throw new Exception('invalid json');
  }
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error' => 'failed to load smtp settings']);
  exit;
}

$host = trim((string)($cfg['host'] ?? ''));
$port = (int)($cfg['port'] ?? 587);
$secure = (string)($cfg['secure'] ?? 'tls'); // none|ssl|tls
$username = (string)($cfg['username'] ?? '');
$password = (string)($cfg['password'] ?? '');
$fromEmail = trim((string)($cfg['from_email'] ?? ''));
$fromName = trim((string)($cfg['from_name'] ?? ''));
if ($fromEmail === '') $fromEmail = $username;
if ($fromEmail === '' || !filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
  http_response_code(400);
  echo json_encode(['error' => 'invalid from_email in settings']);
  exit;
}

function encHeader($s) {
  if ($s === '') return '';
  return '=?UTF-8?B?' . base64_encode($s) . '?=';
}

function smtp_expect($fp, $codes, &$log) {
  $line = '';
  $resp = '';
  while (($line = fgets($fp, 1000)) !== false) {
    $resp .= $line;
    $log[] = rtrim($line, "\r\n");
    if (preg_match('/^([0-9]{3})[ -]/', $line, $m)) {
      if ($line[3] === ' ') break; // last line
    } else {
      break;
    }
  }
  $code = (int)substr($resp, 0, 3);
  if (!in_array($code, (array)$codes, true)) {
    throw new Exception('unexpected smtp code: ' . $code . ' resp=' . trim($resp));
  }
  return $resp;
}

function smtp_cmd($fp, $cmd, $expect, &$log) {
  if ($cmd !== null) fwrite($fp, $cmd);
  return smtp_expect($fp, $expect, $log);
}

$log = [];
try {
  // 接続
  $errno = 0;
  $errstr = '';
  $timeout = 15;
  $transport = ($secure === 'ssl' || $port === 465) ? 'ssl://' : 'tcp://';
  $endpoint = $transport . $host . ':' . $port;
  $ctx = stream_context_create(['ssl' => [
    'SNI_enabled' => true,
    'peer_name' => $host,
    'verify_peer' => false,
    'verify_peer_name' => false,
    'allow_self_signed' => true
  ]]);
  $fp = @stream_socket_client($endpoint, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $ctx);
  if (!$fp) throw new Exception('connect failed: ' . $errstr . " (#$errno)");
  stream_set_timeout($fp, $timeout);
  smtp_expect($fp, 220, $log);

  // EHLO
  smtp_cmd($fp, "EHLO localhost\r\n", 250, $log);

  // STARTTLS（必要なら）
  if ($secure === 'tls' && $port !== 465) {
    smtp_cmd($fp, "STARTTLS\r\n", 220, $log);
    if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
      throw new Exception('STARTTLS failed');
    }
    // 再度EHLO
    smtp_cmd($fp, "EHLO localhost\r\n", 250, $log);
  }

  // AUTH LOGIN
  if ($username !== '' || $password !== '') {
    smtp_cmd($fp, "AUTH LOGIN\r\n", 334, $log);
    smtp_cmd($fp, base64_encode($username) . "\r\n", 334, $log);
    smtp_cmd($fp, base64_encode($password) . "\r\n", 235, $log);
  }

  // MAIL/RCPT/DATA
  smtp_cmd($fp, "MAIL FROM:<$fromEmail>\r\n", [250, 251], $log);
  smtp_cmd($fp, "RCPT TO:<$to>\r\n", [250, 251], $log);
  smtp_cmd($fp, "DATA\r\n", 354, $log);

  $subject = 'SMTPテスト送信';
  $date = date('r');
  $headers = '';
  $headers .= 'From: ' . ($fromName !== '' ? encHeader($fromName) . ' ' : '') . "<{$fromEmail}>\r\n";
  $headers .= 'To: ' . "<{$to}>\r\n";
  $headers .= 'Subject: ' . encHeader($subject) . "\r\n";
  $headers .= "MIME-Version: 1.0\r\n";
  $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
  $headers .= "Content-Transfer-Encoding: 8bit\r\n";
  $headers .= 'Date: ' . $date . "\r\n";

  $body = "このメールはSMTP接続テストです。\r\n送信時刻: " . date('Y-m-d H:i:s') . "\r\nホスト: {$host}:{$port} ({$secure})\r\n";

  $msg = $headers . "\r\n" . $body . "\r\n";
  fwrite($fp, $msg . ".\r\n");
  smtp_expect($fp, 250, $log);

  // 終了
  fwrite($fp, "QUIT\r\n");
  @fclose($fp);

  echo json_encode(['ok' => true, 'message' => 'テストメールを送信しました', 'to' => $to, 'host' => $host, 'port' => $port, 'secure' => $secure]);
} catch (Throwable $e) {
  if (isset($fp) && is_resource($fp)) {
    @fclose($fp);
  }
  http_response_code(500);
  echo json_encode(['error' => 'send failed', 'detail' => $e->getMessage(), 'log' => $log]);
}
