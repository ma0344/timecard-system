<?php
// api/settings_get.php
session_start();
header('Content-Type: application/json');
require_once '../db_config.php';

// 管理者権限チェック
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'not logged in']);
    exit;
}
$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare('SELECT role FROM users WHERE id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user || $user['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'forbidden']);
    exit;
}
// 設定取得
$sql = "SELECT period_start, period_end, rounding_type, rounding_unit, work_hours, work_minutes FROM settings ORDER BY id DESC LIMIT 1";
$stmt = $pdo->query($sql);
if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo json_encode($row);
} else {
    // デフォルト値
    echo json_encode([
        'period_start' => 1,
        'period_end' => 31,
        'rounding_type' => 'floor',
        'rounding_unit' => 1,
        'work_hours' => 8,
        'work_minutes' => 0
    ]);
    // settingsテーブルにレコードがない場合の初期値
}
