<?php
// api/leave_requests_decide.php
// トークンでの承認/却下（最小実装：ログイン不要、ワンタイムトークン）
header('Content-Type: application/json');
require_once '../db_config.php';

// メソッド強制
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'method not allowed']);
    exit;
}

// 簡易 Referer チェック（ヘッダがある場合のみ同一ホストを要求）
$ref = isset($_SERVER['HTTP_REFERER']) ? trim((string)$_SERVER['HTTP_REFERER']) : '';
if ($ref !== '') {
    $refHost = parse_url($ref, PHP_URL_HOST);
    $srvHost = $_SERVER['HTTP_HOST'] ?? '';
    if ($refHost && $srvHost && !preg_match('/^' . preg_quote($refHost, '/') . '(?:\:\d+)?$/', $srvHost)) {
        http_response_code(403);
        echo json_encode(['error' => 'forbidden']);
        exit;
    }
}

// レート制限（IP単位）
function rate_limit($pdo, $endpoint, $limit, $windowSec) {
    $pdo->exec('CREATE TABLE IF NOT EXISTS request_rate_limit (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        ip VARCHAR(45) NOT NULL,
        endpoint VARCHAR(100) NOT NULL,
        period_start DATETIME NOT NULL,
        count INT NOT NULL DEFAULT 0,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_ip_ep (ip, endpoint)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $upd = $pdo->prepare('UPDATE request_rate_limit
        SET count = IF(TIMESTAMPDIFF(SECOND, period_start, NOW()) < ?, count+1, 1),
            period_start = IF(TIMESTAMPDIFF(SECOND, period_start, NOW()) < ?, period_start, NOW())
        WHERE ip = ? AND endpoint = ?');
    $upd->execute([$windowSec, $windowSec, $ip, $endpoint]);
    if ($upd->rowCount() === 0) {
        $ins = $pdo->prepare('INSERT INTO request_rate_limit (ip, endpoint, period_start, count) VALUES (?,?,NOW(),1)');
        try {
            $ins->execute([$ip, $endpoint]);
        } catch (Throwable $e) {
        }
    }
    $sel = $pdo->prepare('SELECT count, period_start FROM request_rate_limit WHERE ip = ? AND endpoint = ?');
    $sel->execute([$ip, $endpoint]);
    $r = $sel->fetch(PDO::FETCH_ASSOC);
    if (!$r) return true;
    if ((int)$r['count'] > $limit && (time() - strtotime($r['period_start'])) < $windowSec) return false;
    return true;
}

if (!rate_limit($pdo, 'leave_requests_decide', 10, 60)) {
    usleep(random_int(100000, 300000));
    http_response_code(429);
    echo json_encode(['error' => 'too many requests']);
    exit;
}

$data = $_POST;
if (empty($data)) {
    // JSON でも受け付け
    $raw = json_decode(file_get_contents('php://input'), true);
    if (is_array($raw)) $data = $raw;
}

$token = isset($data['token']) ? trim((string)$data['token']) : '';
$action = isset($data['action']) ? trim((string)$data['action']) : '';
if ($token === '' || !in_array($action, ['approve', 'reject'], true)) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid params']);
    exit;
}

try {
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

    // 有効なトークンか確認
    $tokenHash = hash('sha256', $token);
    $stmt = $pdo->prepare('SELECT id, status, approve_token_expires_at FROM leave_requests WHERE (approve_token = ? OR approve_token_hash = ?) LIMIT 1');
    $stmt->execute([$token, $tokenHash]);
    $req = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$req) {
        usleep(random_int(100000, 300000));
        http_response_code(404);
        echo json_encode(['error' => 'not found']);
        exit;
    }
    if ($req['approve_token_expires_at'] && strtotime($req['approve_token_expires_at']) < time()) {
        http_response_code(410);
        echo json_encode(['error' => 'token expired']);
        exit;
    }
    if ($req['status'] !== 'pending') {
        echo json_encode(['warning' => 'already decided', 'status' => $req['status']]);
        exit;
    }

    $newStatus = $action === 'approve' ? 'approved' : 'rejected';
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
    $upd = $pdo->prepare('UPDATE leave_requests SET status = ?, decided_at = NOW(), decided_ip = ?, decided_user_agent = ?, approve_token = NULL, approve_token_expires_at = NULL WHERE id = ?');
    $upd->execute([$newStatus, $ip, $ua, (int)$req['id']]);

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
    $al = $pdo->prepare('INSERT INTO leave_request_audit (request_id, action, actor_type, actor_id, ip, user_agent) VALUES (?,?,?,?,?,?)');
    $al->execute([(int)$req['id'], $action, 'token', null, $ip, $ua]);

    echo json_encode(['ok' => true, 'status' => $newStatus]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'db error']);
}
