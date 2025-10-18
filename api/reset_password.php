<?php
// api/reset_password.php
session_start();
header('Content-Type: application/json');
require_once '../db_config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'not logged in']);
    exit;
}
$userId = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);
$newPassword = $data['password'] ?? null;
if (!$newPassword) {
    http_response_code(400);
    echo json_encode(['error' => 'password required']);
    exit;
}
$hash = password_hash($newPassword, PASSWORD_DEFAULT);
$stmt = $pdo->prepare('UPDATE users SET password_hash = ?, must_reset_password = 0 WHERE id = ?');
$stmt->execute([$hash, $userId]);
echo json_encode(['success' => true]);
