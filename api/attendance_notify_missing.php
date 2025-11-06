<?php
// api/attendance_notify_missing.php
// 管理者が未打刻ユーザーへ通知を送るエンドポイント
session_start();
header('Content-Type: application/json');
require_once '../db_config.php';

// メソッド強制
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'method not allowed']);
    exit;
}

// Referer がある場合は同一ホストを要求
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

// レート制限（IP単位・管理操作向け）
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

list($lim, $win) = rate_cfg($pdo, 'attendance_notify_missing', 60, 300);
if (!rate_limit($pdo, 'attendance_notify_missing', $lim, $win)) {
    usleep(random_int(100000, 300000));
    http_response_code(429);
    echo json_encode(['error' => 'too many requests']);
    exit;
}

// 認証（管理者のみ）
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'not logged in']);
    exit;
}
$adminId = (int)($_SESSION['user_id'] ?? 0);
$stmt = $pdo->prepare('SELECT role FROM users WHERE id = ?');
$stmt->execute([$adminId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row || $row['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'forbidden']);
    exit;
}

// 入力
$data = $_POST;
if (empty($data)) {
    $raw = json_decode(file_get_contents('php://input'), true);
    if (is_array($raw)) $data = $raw;
}
$userId = isset($data['user_id']) ? (int)$data['user_id'] : 0;
$date = isset($data['date']) ? trim((string)$data['date']) : '';
if ($userId <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid params']);
    exit;
}

try {
    // 通知テーブル（存在しない場合は作成）
    $pdo->exec('CREATE TABLE IF NOT EXISTS notifications (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        type VARCHAR(50) NOT NULL,
        title VARCHAR(200) NOT NULL,
        body TEXT NULL,
        link VARCHAR(255) NULL,
        status ENUM("unread","read") NOT NULL DEFAULT "unread",
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        read_at DATETIME NULL,
        INDEX(user_id), INDEX(status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

    // 本文生成（例: 3月5日）
    $parts = explode('-', $date);
    $m = (int)$parts[1];
    $d = (int)$parts[2];
    $title = '勤務記録未入力のお願い';
    $body = sprintf('%d月%d日の勤務記録がありません。入力をお願いします。', $m, $d);
    $link = './attendance_list.html';

    $ins = $pdo->prepare('INSERT INTO notifications (user_id, type, title, body, link) VALUES (?,?,?,?,?)');
    $ins->execute([$userId, 'attendance_missing_reminder', $title, $body, $link]);

    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'db error']);
}
