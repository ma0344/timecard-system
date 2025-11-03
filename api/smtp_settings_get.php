<?php
// api/smtp_settings_get.php
// SMTP設定の取得（app_settingsテーブルのJSON）
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

$default = [
    'host' => '',
    'port' => 587,
    'secure' => 'tls', // none|ssl|tls
    'username' => '',
    'password' => '',
    'from_email' => '',
    'from_name' => ''
];

try {
    // テーブルが存在しない場合は作成（最小）
    $pdo->exec('CREATE TABLE IF NOT EXISTS app_settings (
        `key` VARCHAR(191) PRIMARY KEY,
        `value` TEXT,
        `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

    $stmt = $pdo->prepare('SELECT value FROM app_settings WHERE `key` = ? LIMIT 1');
    $stmt->execute(['smtp']);
    $val = $stmt->fetchColumn();
    if ($val) {
        $obj = json_decode($val, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($obj)) {
            echo json_encode(array_merge($default, $obj));
            exit;
        }
    }
    echo json_encode($default);
} catch (Throwable $e) {
    // 取得失敗時はデフォルト
    echo json_encode($default);
}
