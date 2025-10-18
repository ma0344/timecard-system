<?php
// api/admin_user_create.php
session_start();
header('Content-Type: application/json');
require_once '../db_config.php';

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
// POSTでユーザー名・権限・use_vehicleを受け取る
$data = json_decode(file_get_contents('php://input'), true);
$name = $data['name'] ?? null;
$role = $data['role'] ?? 'user';
$use_vehicle = array_key_exists('use_vehicle', $data) ? (int)$data['use_vehicle'] : 1;
if (!$name) {
    http_response_code(400);
    echo json_encode(['error' => 'name required']);
    exit;
}
// ユーザー名重複チェック
$stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE name = ?');
$stmt->execute([$name]);
if ($stmt->fetchColumn() > 0) {
    http_response_code(409);
    echo json_encode(['error' => 'duplicate name']);
    exit;
}
$hash = password_hash('netone', PASSWORD_DEFAULT);
$stmt = $pdo->prepare('INSERT INTO users (name, role, password_hash, visible, must_reset_password, use_vehicle) VALUES (?, ?, ?, 1, 1, ?)');
$stmt->execute([$name, $role, $hash, $use_vehicle]);
echo json_encode(['success' => true]);
