<?php
// api/paid_leave_grant.php
session_start();
header('Content-Type: application/json');
require_once '../db_config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'not logged in']);
    exit;
}

// 管理者チェック
$adminId = $_SESSION['user_id'];
$stmt = $pdo->prepare('SELECT role FROM users WHERE id = ?');
$stmt->execute([$adminId]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$admin || $admin['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'forbidden']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$targetId = isset($data['user_id']) ? (int)$data['user_id'] : null;
$grantDate = $data['grant_date'] ?? null;
$grantHours = isset($data['grant_hours']) ? (float)$data['grant_hours'] : null;
$expireDate = $data['expire_date'] ?? null; // null も許容

if (!$targetId || !$grantDate || $grantHours === null) {
    http_response_code(400);
    echo json_encode(['error' => 'user_id, grant_date, grant_hours required']);
    exit;
}

// ユーザー存在確認
$stmt = $pdo->prepare('SELECT id FROM users WHERE id = ?');
$stmt->execute([$targetId]);
if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
    http_response_code(404);
    echo json_encode(['error' => 'user not found']);
    exit;
}

try {
    $stmt = $pdo->prepare('INSERT INTO paid_leaves (user_id, grant_date, grant_hours, expire_date) VALUES (?, ?, ?, ?)');
    $stmt->execute([$targetId, $grantDate, $grantHours, $expireDate]);
    $id = $pdo->lastInsertId();
    echo json_encode(['success' => true, 'id' => (int)$id]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'db error']);
}
