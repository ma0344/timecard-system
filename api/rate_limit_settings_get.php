<?php
// admin-only: get rate limit settings from app_settings
session_start();
header('Content-Type: application/json');
require_once '../db_config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'not logged in']);
    exit;
}
$uid = (int)$_SESSION['user_id'];
$st = $pdo->prepare('SELECT role FROM users WHERE id=?');
$st->execute([$uid]);
$row = $st->fetch(PDO::FETCH_ASSOC);
if (!$row || $row['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'forbidden']);
    exit;
}

try {
    $pdo->exec('CREATE TABLE IF NOT EXISTS app_settings (`key` VARCHAR(191) PRIMARY KEY, `value` TEXT, `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    $st = $pdo->prepare('SELECT value FROM app_settings WHERE `key`=? LIMIT 1');
    $st->execute(['rate_limit']);
    $val = $st->fetchColumn();
    $json = $val ? json_decode($val, true) : null;
    if (!is_array($json)) {
        $json = [
            'leave_requests_decide' => ['limit' => 10, 'window' => 60],
            'leave_requests_approve_link' => ['limit' => 30, 'window' => 300],
            'leave_requests_decide_admin' => ['limit' => 30, 'window' => 300]
        ];
    }
    echo json_encode(['ok' => true, 'settings' => $json]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'db error']);
}
