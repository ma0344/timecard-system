<?php
// notifications_read.php - mark user's notifications read
session_start();
header('Content-Type: application/json');
require_once '../db_config.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'method not allowed']);
    exit;
}
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'not logged in']);
    exit;
}
$uid = (int)$_SESSION['user_id'];

try {
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
    $raw = json_decode(file_get_contents('php://input'), true) ?: [];
    $ids = isset($raw['ids']) && is_array($raw['ids']) ? array_map('intval', $raw['ids']) : [];
    if ($ids) {
        $in = implode(',', array_fill(0, count($ids), '?'));
        $sql = 'UPDATE notifications SET status="read", read_at=NOW() WHERE user_id=? AND id IN (' . $in . ') AND status="unread"';
        $st = $pdo->prepare($sql);
        $st->execute(array_merge([$uid], $ids));
    } else {
        $st = $pdo->prepare('UPDATE notifications SET status="read", read_at=NOW() WHERE user_id=? AND status="unread"');
        $st->execute([$uid]);
    }
    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'db error']);
}
