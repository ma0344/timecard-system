<?php
// api/notify_settings_save.php
// 通知設定（宛先/有効フラグ）を保存
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
$enabled = isset($data['enabled']) ? (bool)$data['enabled'] : true;
$recipients = isset($data['recipients']) ? trim((string)$data['recipients']) : '';

// 軽い検証：カンマ区切りメール。空は許容（後続で未設定扱い）。
if ($recipients !== '') {
    $list = array_map('trim', explode(',', $recipients));
    foreach ($list as $addr) {
        if ($addr !== '' && !filter_var($addr, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['error' => 'invalid email: ' . $addr]);
            exit;
        }
    }
}

try {
    $pdo->exec('CREATE TABLE IF NOT EXISTS app_settings (`key` VARCHAR(191) PRIMARY KEY, `value` TEXT, `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    $payload = json_encode(['enabled' => $enabled, 'recipients' => $recipients], JSON_UNESCAPED_UNICODE);
    $stmt = $pdo->prepare('INSERT INTO app_settings(`key`, `value`) VALUES(?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)');
    $stmt->execute(['notify', $payload]);
    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'db error']);
}
