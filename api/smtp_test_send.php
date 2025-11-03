<?php
// api/smtp_test_send.php
// SMTPホストへのTCP接続とバナー取得のみ（認証/送信は未実装）
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
$host = isset($data['host']) ? trim((string)$data['host']) : '';
$port = isset($data['port']) ? (int)$data['port'] : 587;
$secure = isset($data['secure']) ? (string)$data['secure'] : 'tls'; // none|ssl|tls
if ($host === '' || $port <= 0 || $port > 65535) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid host/port']);
    exit;
}

// 事前にDNS解決を試み、わかりやすいメッセージを返す
$isIp = filter_var($host, FILTER_VALIDATE_IP) !== false;
if (!$isIp) {
    $resolved = @gethostbyname($host);
    if (!$resolved || $resolved === $host) {
        echo json_encode(['ok' => false, 'message' => 'DNS解決に失敗しました（ホスト名が正しいか、サーバーのDNS設定/外向き通信をご確認ください）']);
        exit;
    }
}

// 試験的に接続 -> バナー読み取り -> QUIT
$timeout = 7; // seconds
$errno = 0;
$errstr = '';
// 暗号化方式のヒント（465=SSLが一般的）
$useHost = $host;
if ($secure === 'ssl' || $port === 465) {
    $useHost = 'ssl://' . $host;
}

$fp = @fsockopen($useHost, $port, $errno, $errstr, $timeout);
if (!$fp) {
    echo json_encode(['ok' => false, 'message' => '接続に失敗: ' . $errstr . " (#$errno)", 'host' => $host, 'port' => $port, 'secure' => $secure]);
    exit;
}
stream_set_timeout($fp, $timeout);
$banner = fgets($fp, 512) ?: '';
@fwrite($fp, "QUIT\r\n");
@fclose($fp);

echo json_encode([
    'ok' => true,
    'message' => '接続成功（送信未実装）',
    'host' => $host,
    'port' => $port,
    'secure' => $secure,
    'banner' => trim($banner)
]);
