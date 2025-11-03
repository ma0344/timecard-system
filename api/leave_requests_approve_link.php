<?php
// api/leave_requests_approve_link.php
// 承認フォーム表示用の情報取得（トークン検証）
header('Content-Type: application/json');
require_once '../db_config.php';

$ref = isset($_SERVER['HTTP_REFERER']) ? trim((string)$_SERVER['HTTP_REFERER']) : '';
// 設定からレート制限の値を取得
function rate_cfg($pdo, $endpoint, $defL, $defW) {
    try {
        $st = $pdo->prepare('SELECT value FROM app_settings WHERE `key`=? LIMIT 1');
        $st->execute(['rate_limit']);
        $val = $st->fetchColumn();
        $j = $val ? json_decode($val, true) : null;
        if (is_array($j) && isset($j[$endpoint])) {
            $l = isset($j[$endpoint]['limit']) ? (int)$j[$endpoint]['limit'] : $defL;
            $w = isset($j[$endpoint]['window']) ? (int)$j[$endpoint]['window'] : $defW;
            if ($l > 0 && $w > 0) return [$l, $w];
        }
    } catch (Throwable $e) {
    }
    return [$defL, $defW];
}

// GETだが、総当たり対策として軽いレート制限を適用
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

list($lim, $win) = rate_cfg($pdo, 'leave_requests_approve_link', 30, 300);
if (!rate_limit($pdo, 'leave_requests_approve_link', $lim, $win)) {
    usleep(random_int(100000, 300000));
    http_response_code(429);
    echo json_encode(['error' => 'too many requests']);
    exit;
}

$token = isset($_GET['token']) ? trim((string)$_GET['token']) : '';
if ($token === '') {
    http_response_code(400);
    echo json_encode(['error' => 'token required']);
    exit;
}

try {
    // テーブル存在しない場合に備えつつ問い合わせ
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

    $tokenHash = hash('sha256', $token);
    $stmt = $pdo->prepare('SELECT lr.*, u.name FROM leave_requests lr JOIN users u ON u.id = lr.user_id WHERE (lr.approve_token = ? OR lr.approve_token_hash = ?) LIMIT 1');
    $stmt->execute([$token, $tokenHash]);
    $req = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$req) {
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
    // フォーム表示（トークン経由）の監査ログ
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
    $al = $pdo->prepare('INSERT INTO leave_request_audit (request_id, action, actor_type, actor_id, ip, user_agent) VALUES (?,?,?,?,?,?)');
    $al->execute([(int)$req['id'], 'open', 'token', null, $ip, $ua]);

    echo json_encode([
        'ok' => true,
        'request_id' => (int)$req['id'],
        'name' => $req['name'],
        'used_date' => $req['used_date'],
        'hours' => (float)$req['hours'],
        'reason' => $req['reason'],
        'status' => $req['status']
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'db error']);
}
