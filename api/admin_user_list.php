<?php
// api/admin_user_list.php
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

// ユーザー一覧取得
$stmt = $pdo->query('SELECT id, name, role, visible, use_vehicle, contract_hours_per_day FROM users ORDER BY id');
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($users);
