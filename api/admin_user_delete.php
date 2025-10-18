<?php
// api/admin_user_delete.php
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
// POSTでユーザーIDを受け取る
$data = json_decode(file_get_contents('php://input'), true);
$targetId = $data['id'] ?? null;
if (!$targetId) {
    http_response_code(400);
    echo json_encode(['error' => 'id required']);
    exit;
}
// visible=0に更新
$stmt = $pdo->prepare('UPDATE users SET visible = 0 WHERE id = ?');
$stmt->execute([$targetId]);
echo json_encode(['success' => true]);
