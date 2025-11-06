<?php
// api/leave_requests_create.php
// 新規の有休申請を作成し、承認リンク付きメールを通知（手動宛先）
session_start();
header('Content-Type: application/json');
require_once '../db_config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'not logged in']);
    exit;
}
$userId = (int)$_SESSION['user_id'];

$data = json_decode(file_get_contents('php://input'), true);
$used_date = isset($data['used_date']) ? trim((string)$data['used_date']) : '';
$hours = isset($data['hours']) ? (float)$data['hours'] : 0;
$reason = isset($data['reason']) ? trim((string)$data['reason']) : '';
if (!$used_date || !$hours || $hours <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid params']);
    exit;
}

function rand_token($len = 48) {
    return rtrim(strtr(base64_encode(random_bytes($len)), '+/', '-_'), '=');
}

try {
    // ensure table
    $pdo->exec('CREATE TABLE IF NOT EXISTS leave_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        used_date DATE NOT NULL,
        hours DECIMAL(6,2) NOT NULL,
        reason TEXT NULL,
        status ENUM("pending","approved","rejected") NOT NULL DEFAULT "pending",
        approver_user_id INT NULL,
        decided_at DATETIME NULL,
        decided_ip VARCHAR(45) NULL,
        decided_user_agent VARCHAR(255) NULL,
        approve_token VARCHAR(128) UNIQUE,
        approve_token_hash CHAR(64) NULL,
        approve_token_expires_at DATETIME NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX (status), INDEX (approve_token), INDEX (approve_token_hash)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

    $token = rand_token(32);
    $tokenHash = hash('sha256', $token);
    $expires = date('Y-m-d H:i:s', time() + 72 * 3600);
    // 平文の approve_token は保存しない（NULL）
    $stmt = $pdo->prepare('INSERT INTO leave_requests (user_id, used_date, hours, reason, status, approve_token, approve_token_hash, approve_token_expires_at) VALUES (?,?,?,?,"pending",NULL,?,?)');
    $stmt->execute([$userId, $used_date, $hours, $reason, $tokenHash, $expires]);
    $id = (int)$pdo->lastInsertId();

    // 監査テーブル（存在しない場合は作成）
    $pdo->exec('CREATE TABLE IF NOT EXISTS leave_request_audit (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        request_id INT NOT NULL,
        action ENUM("create","open","approve","reject") NOT NULL,
        actor_type ENUM("user","admin","token","system") NOT NULL,
        actor_id INT NULL,
        ip VARCHAR(45) NULL,
        user_agent VARCHAR(255) NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX (request_id), INDEX (action), INDEX (actor_type)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    // 申請作成の監査ログ
    $actorType = isset($_SESSION['user_id']) ? 'user' : 'system';
    $actorId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
    $al = $pdo->prepare('INSERT INTO leave_request_audit (request_id, action, actor_type, actor_id, ip, user_agent) VALUES (?,?,?,?,?,?)');
    $al->execute([$id, 'create', $actorType, $actorId, $ip, $ua]);

    // 申請者名取得
    $un = $pdo->prepare('SELECT name FROM users WHERE id = ?');
    $un->execute([$userId]);
    $userName = (string)($un->fetchColumn() ?: '');

    // 通知設定読み込み
    $pdo->exec('CREATE TABLE IF NOT EXISTS app_settings (`key` VARCHAR(191) PRIMARY KEY, `value` TEXT, `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    $ns = $pdo->prepare('SELECT value FROM app_settings WHERE `key` = ? LIMIT 1');
    $ns->execute(['notify']);
    $notify = json_decode($ns->fetchColumn() ?: '{}', true) ?: [];
    $enabled = isset($notify['enabled']) ? (bool)$notify['enabled'] : true;
    $recipients = isset($notify['recipients']) ? trim((string)$notify['recipients']) : '';

    // SMTP設定読み込み
    $ss = $pdo->prepare('SELECT value FROM app_settings WHERE `key` = ? LIMIT 1');
    $ss->execute(['smtp']);
    $smtp = json_decode($ss->fetchColumn() ?: '{}', true) ?: [];

    // メール送信（有効時のみ）
    $sent = false;
    $sendError = null;
    if ($enabled && $recipients !== '' && is_array($smtp)) {
        $toList = array_values(array_filter(array_map('trim', explode(',', $recipients)), function ($x) {
            return $x !== '';
        }));
        if (count($toList) > 0) {
            // メール本文と件名
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
            $link = $scheme . '://' . $host . $basePath . '/../approval.html?token=' . urlencode($token);
            $subject = '【有休申請】' . $userName . ' さんの有給休暇申請: ' . ' ' . $used_date . ' ' . number_format($hours, 1) . 'h';
            $body = "以下の有休休暇が申請されました。\r\n\r\n"
                . "申請者: {$userName}\r\n"
                . "取得希望日: {$used_date}\r\n"
                . "取得時間: " . number_format($hours, 1) . "h\r\n"
                . ($reason !== '' ? "理由: {$reason}\r\n" : "")
                . "\r\n承認フォーム: {$link}\r\n\r\n"
                . "このリンクは72時間有効です。";

            // 送信（単純な複数宛）
            $smtpHost = (string)($smtp['host'] ?? '');
            $smtpPort = (int)($smtp['port'] ?? 587);
            $secure = (string)($smtp['secure'] ?? 'tls');
            $username = (string)($smtp['username'] ?? '');
            $password = (string)($smtp['password'] ?? '');
            $fromEmail = (string)($smtp['from_email'] ?? $username);
            $fromName = (string)($smtp['from_name'] ?? '');

            // 簡易送信: toListを1件ずつ送る
            foreach ($toList as $to) {
                // 直接APIを呼ぶ代わりに内部関数がないため、送信ロジックをここにインライン（重複OK最小実装）
                try {
                    // 接続
                    $errno = 0;
                    $errstr = '';
                    $timeout = 15;
                    $transport = ($secure === 'ssl' || $smtpPort === 465) ? 'ssl://' : 'tcp://';
                    $endpoint = $transport . $smtpHost . ':' . $smtpPort;
                    $ctx = stream_context_create(['ssl' => ['SNI_enabled' => true, 'peer_name' => $smtpHost, 'verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true]]);
                    $fp = @stream_socket_client($endpoint, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $ctx);
                    if (!$fp) throw new Exception('connect failed: ' . $errstr);
                    stream_set_timeout($fp, $timeout);
                    $log = [];
                    $expect = function ($codes) use ($fp, &$log) {
                        $line = '';
                        $resp = '';
                        while (($line = fgets($fp, 1000)) !== false) {
                            $resp .= $line;
                            $log[] = rtrim($line, "\r\n");
                            if (preg_match('/^([0-9]{3})[ -]/', $line, $m)) {
                                if ($line[3] === ' ') break;
                            } else {
                                break;
                            }
                        }
                        $code = (int)substr($resp, 0, 3);
                        if (!in_array($code, (array)$codes, true)) throw new Exception('unexpected code ' . $code);
                    };
                    $cmd = function ($c, $codes) use ($fp, $expect) {
                        if ($c !== null) fwrite($fp, $c);
                        $expect($codes);
                    };
                    $expect(220);
                    $cmd("EHLO localhost\r\n", 250);
                    if ($secure === 'tls' && $smtpPort !== 465) {
                        $cmd("STARTTLS\r\n", 220);
                        if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) throw new Exception('STARTTLS failed');
                        $cmd("EHLO localhost\r\n", 250);
                    }
                    if ($username !== '' || $password !== '') {
                        $cmd("AUTH LOGIN\r\n", 334);
                        $cmd(base64_encode($username) . "\r\n", 334);
                        $cmd(base64_encode($password) . "\r\n", 235);
                    }
                    $cmd("MAIL FROM:<{$fromEmail}>\r\n", [250, 251]);
                    $cmd("RCPT TO:<{$to}>\r\n", [250, 251]);
                    $cmd("DATA\r\n", 354);
                    $hdr = 'From: ' . ($fromName !== '' ? '=?UTF-8?B?' . base64_encode($fromName) . '?= ' : '') . "<{$fromEmail}>\r\n" .
                        'To: ' . "<{$to}>\r\n" .
                        'Subject: ' . '=?UTF-8?B?' . base64_encode($subject) . "?=\r\n" .
                        "MIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n" .
                        'Date: ' . date('r') . "\r\n";
                    fwrite($fp, $hdr . "\r\n" . $body . "\r\n.\r\n");
                    $expect(250);
                    fwrite($fp, "QUIT\r\n");
                    @fclose($fp);
                    $sent = true;
                } catch (Throwable $e) {
                    $sendError = $e->getMessage();
                }
            }
        }
    }

    echo json_encode(['ok' => true, 'id' => $id, 'notified' => $sent, 'notify_error' => $sendError]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'db error']);
}
