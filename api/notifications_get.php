<?php
// notifications_get.php - current user's notifications (unread first)
session_start();
header('Content-Type: application/json');
require_once '../db_config.php';

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
    $onlyUnread = isset($_GET['only_unread']) ? (int)$_GET['only_unread'] : 0;
    if ($onlyUnread) {
        $st = $pdo->prepare('SELECT id, type, title, body, link, status, created_at FROM notifications WHERE user_id=? AND status="unread" ORDER BY created_at DESC LIMIT 50');
        $st->execute([$uid]);
    } else {
        $st = $pdo->prepare('SELECT id, type, title, body, link, status, created_at FROM notifications WHERE user_id=? ORDER BY status ASC, created_at DESC LIMIT 50');
        $st->execute([$uid]);
    }
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    $cnt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id=? AND status="unread"');
    $cnt->execute([$uid]);
    $unread = (int)$cnt->fetchColumn();
    echo json_encode(['ok' => true, 'unread' => $unread, 'items' => $rows]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'db error']);
}
