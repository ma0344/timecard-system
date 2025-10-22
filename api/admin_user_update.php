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
$contract_hours_per_day = array_key_exists('contract_hours_per_day', $data) ? (float)$data['contract_hours_per_day'] : null;
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
try {
    $pdo->beginTransaction();
    // users 側更新
    $stmt = $pdo->prepare('UPDATE users SET name = ?, role = ? WHERE id = ?');
    $stmt->execute([$name, $role, $targetId]);

    // user_detail 側 upsert（指定が無い場合は現状維持のため既存値を採用）
    if ($use_vehicle !== null || $contract_hours_per_day !== null) {
        // 既存値取得
        $stmt = $pdo->prepare('SELECT use_vehicle, contract_hours_per_day FROM user_detail WHERE user_id = ?');
        $stmt->execute([$targetId]);
        $detail = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $useV = ($use_vehicle !== null) ? $use_vehicle : (isset($detail['use_vehicle']) ? (int)$detail['use_vehicle'] : 1);
        $contractH = ($contract_hours_per_day !== null) ? $contract_hours_per_day : (isset($detail['contract_hours_per_day']) ? (float)$detail['contract_hours_per_day'] : 8.0);

        $stmt = $pdo->prepare('INSERT INTO user_detail (user_id, use_vehicle, contract_hours_per_day, full_time)
                               VALUES (?, ?, ?, 1)
                               ON DUPLICATE KEY UPDATE use_vehicle = VALUES(use_vehicle), contract_hours_per_day = VALUES(contract_hours_per_day)');
        $stmt->execute([$targetId, $useV, $contractH]);
    }
    $pdo->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'db error']);
}
