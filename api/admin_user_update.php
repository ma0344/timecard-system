<?php
// api/admin_user_update.php
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
// POSTでユーザーID・新ユーザー名・権限を受け取る
$data = json_decode(file_get_contents('php://input'), true);
$targetId = $data['id'] ?? null;
$name = $data['name'] ?? null;
$role = $data['role'] ?? null;
$use_vehicle = array_key_exists('use_vehicle', $data) ? (int)$data['use_vehicle'] : null;
$contract_hours_per_day = array_key_exists('contract_hours_per_day', $data) ? $data['contract_hours_per_day'] : null;
if (!$targetId || !$name || !$role) {
    http_response_code(400);
    echo json_encode(['error' => 'id, name, role required']);
    exit;
}
// ユーザー名重複チェック（自分自身は除外）
$stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE name = ? AND id != ?');
$stmt->execute([$name, $targetId]);
if ($stmt->fetchColumn() > 0) {
    http_response_code(409);
    echo json_encode(['error' => 'duplicate name']);
    exit;
}
// 更新SQLを動的に組み立て
$fields = ['name = ?', 'role = ?'];
$params = [$name, $role];
if ($use_vehicle !== null) {
    $fields[] = 'use_vehicle = ?';
    $params[] = $use_vehicle;
}
if ($contract_hours_per_day !== null && $contract_hours_per_day !== '') {
    $fields[] = 'contract_hours_per_day = ?';
    $params[] = $contract_hours_per_day;
}
$params[] = $targetId;
$sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
echo json_encode(['success' => true]);
