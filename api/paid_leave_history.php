<?php
// api/paid_leave_history.php
session_start();
header('Content-Type: application/json');
require_once '../db_config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'not logged in']);
    exit;
}

// 管理者のみ
$adminId = $_SESSION['user_id'];
$stmt = $pdo->prepare('SELECT role FROM users WHERE id = ?');
$stmt->execute([$adminId]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$admin || $admin['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'forbidden']);
    exit;
}

$targetId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
$limit = isset($_GET['limit']) ? max(1, min(200, (int)$_GET['limit'])) : 50;
if (!$targetId) {
    http_response_code(400);
    echo json_encode(['error' => 'user_id required']);
    exit;
}

$stmt = $pdo->prepare('SELECT id, grant_date, grant_hours, expire_date FROM paid_leaves WHERE user_id = ? ORDER BY grant_date DESC, id DESC LIMIT ?');
$stmt->bindValue(1, $targetId, PDO::PARAM_INT);
$stmt->bindValue(2, $limit, PDO::PARAM_INT);
$stmt->execute();
$grants = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare('SELECT id, paid_leave_id, used_date, used_hours, reason FROM paid_leave_logs WHERE user_id = ? ORDER BY used_date DESC, id DESC LIMIT ?');
$stmt->bindValue(1, $targetId, PDO::PARAM_INT);
$stmt->bindValue(2, $limit, PDO::PARAM_INT);
$stmt->execute();
$uses = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'user_id' => $targetId,
    'grants' => $grants,
    'uses' => $uses,
]);
