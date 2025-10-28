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
// POSTでユーザー名・権限・use_vehicle・contract_hours_per_dayを受け取る
$data = json_decode(file_get_contents('php://input'), true);
$name = $data['name'] ?? null;
$role = $data['role'] ?? 'user';
$use_vehicle = array_key_exists('use_vehicle', $data) ? (int)$data['use_vehicle'] : 1;
$contract_hours_per_day = array_key_exists('contract_hours_per_day', $data) ? (float)$data['contract_hours_per_day'] : 8.0;
if (!$name) {
    http_response_code(400);
    echo json_encode(['error' => 'name required']);
    exit;
}
// ユーザー名重複チェック
$stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE name = ? AND visible = 1');
$stmt->execute([$name]);
if ($stmt->fetchColumn() > 0) {
    http_response_code(409);
    echo json_encode(['error' => 'duplicate name']);
    exit;
}
$hash = password_hash('netone', PASSWORD_DEFAULT);

try {
    $pdo->beginTransaction();
    // users に作成（use_vehicle等は user_detail に分離）
    $stmt = $pdo->prepare('INSERT INTO users (name, role, password_hash, visible, must_reset_password) VALUES (?, ?, ?, 1, 1)');
    $stmt->execute([$name, $role, $hash]);
    $newUserId = (int)$pdo->lastInsertId();
    // user_detail に初期値で作成
    $stmt = $pdo->prepare('INSERT INTO user_detail (user_id, use_vehicle, contract_hours_per_day, full_time) VALUES (?, ?, ?, 1)');
    $stmt->execute([$newUserId, $use_vehicle, $contract_hours_per_day]);
    $pdo->commit();
    echo json_encode(['success' => true, 'id' => $newUserId]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'db error']);
}
