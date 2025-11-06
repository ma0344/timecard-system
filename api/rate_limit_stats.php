<?php
// admin-only: snapshot of request_rate_limit table
session_start();
require_once '../db_config.php';

function is_admin($pdo, $uid) {
    $st = $pdo->prepare('SELECT role FROM users WHERE id = ?');
    $st->execute([$uid]);
    $r = $st->fetch(PDO::FETCH_ASSOC);
    return $r && $r['role'] === 'admin';
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'not logged in']);
    exit;
}
$uid = (int)$_SESSION['user_id'];
if (!is_admin($pdo, $uid)) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'forbidden']);
    exit;
}

$endpoint = isset($_GET['endpoint']) ? trim((string)$_GET['endpoint']) : '';
$limit = isset($_GET['limit']) ? max(1, min(500, (int)$_GET['limit'])) : 100;
$sort = isset($_GET['sort']) ? strtolower($_GET['sort']) : 'count_desc'; // count_desc|time_desc|ip|endpoint

try {
    $pdo->exec('CREATE TABLE IF NOT EXISTS request_rate_limit (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        ip VARCHAR(45) NOT NULL,
        endpoint VARCHAR(100) NOT NULL,
        period_start DATETIME NOT NULL,
        count INT NOT NULL DEFAULT 0,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_ip_ep (ip, endpoint)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

    $where = '';
    $params = [];
    if ($endpoint !== '') {
        $where = 'WHERE endpoint = ?';
        $params[] = $endpoint;
    }

    $order = 'ORDER BY count DESC, updated_at DESC';
    if ($sort === 'time_desc') $order = 'ORDER BY updated_at DESC';
    if ($sort === 'ip') $order = 'ORDER BY ip ASC';
    if ($sort === 'endpoint') $order = 'ORDER BY endpoint ASC, count DESC';

    $sql = "SELECT ip, endpoint, period_start, count, updated_at,
                   TIMESTAMPDIFF(SECOND, period_start, NOW()) AS seconds_elapsed
            FROM request_rate_limit $where $order LIMIT ?";
    $st = $pdo->prepare($sql);
    $params[] = $limit;
    $st->execute($params);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode(['ok' => true, 'items' => $rows]);
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    // 管理者専用API: 例外をサーバーログに記録
    error_log('api/rate_limit_stats.php error: ' . $e->getMessage());
    echo json_encode(['error' => 'db error']);
}
