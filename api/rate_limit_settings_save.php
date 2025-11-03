<?php
// admin-only: save rate limit settings into app_settings
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
$st = $pdo->prepare('SELECT role FROM users WHERE id=?');
$st->execute([$uid]);
$row = $st->fetch(PDO::FETCH_ASSOC);
if (!$row || $row['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'forbidden']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true) ?: [];
$settings = isset($data['settings']) ? $data['settings'] : null;
if (!is_array($settings)) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid settings']);
    exit;
}

function norm($v, $defL, $defW) {
    $l = isset($v['limit']) ? (int)$v['limit'] : $defL;
    if ($l <= 0) $l = $defL;
    $w = isset($v['window']) ? (int)$v['window'] : $defW;
    if ($w <= 0) $w = $defW;
    return ['limit' => $l, 'window' => $w];
}

$conf = [];
$conf['leave_requests_decide'] = norm($settings['leave_requests_decide'] ?? [], 10, 60);
$conf['leave_requests_approve_link'] = norm($settings['leave_requests_approve_link'] ?? [], 30, 300);
$conf['leave_requests_decide_admin'] = norm($settings['leave_requests_decide_admin'] ?? [], 30, 300);

try {
    $pdo->exec('CREATE TABLE IF NOT EXISTS app_settings (`key` VARCHAR(191) PRIMARY KEY, `value` TEXT, `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    $st = $pdo->prepare('INSERT INTO app_settings(`key`,`value`) VALUES(?,?) ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)');
    $st->execute(['rate_limit', json_encode($conf, JSON_UNESCAPED_UNICODE)]);
    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'db error']);
}
