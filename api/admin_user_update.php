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
$has_hire_date = array_key_exists('hire_date', $data);
$hire_date_input = $has_hire_date ? $data['hire_date'] : null; // '' or 'YYYY-MM-DD' or null
// retire_date は任意（保存フォームからの編集にも対応）
$has_retire_date = array_key_exists('retire_date', $data);
$retire_date_input = $has_retire_date ? $data['retire_date'] : null;
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
    if ($use_vehicle !== null || $contract_hours_per_day !== null || $has_hire_date || $has_retire_date) {
        // 既存値取得
        $stmt = $pdo->prepare('SELECT use_vehicle, contract_hours_per_day, hire_date, retire_date FROM user_detail WHERE user_id = ?');
        $stmt->execute([$targetId]);
        $detail = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $useV = ($use_vehicle !== null) ? $use_vehicle : (isset($detail['use_vehicle']) ? (int)$detail['use_vehicle'] : 1);
        $contractH = ($contract_hours_per_day !== null) ? $contract_hours_per_day : (isset($detail['contract_hours_per_day']) ? (float)$detail['contract_hours_per_day'] : 8.0);
        // hire_date は '' -> NULL として扱う
        if ($has_hire_date) {
            $hireDate = ($hire_date_input === '' || $hire_date_input === null) ? null : $hire_date_input;
        } else {
            $hireDate = isset($detail['hire_date']) ? $detail['hire_date'] : null;
        }
        // retire_date も '' -> NULL
        if ($has_retire_date) {
            $retireDate = ($retire_date_input === '' || $retire_date_input === null) ? null : $retire_date_input;
        } else {
            $retireDate = isset($detail['retire_date']) ? $detail['retire_date'] : null;
        }

        $stmt = $pdo->prepare('INSERT INTO user_detail (user_id, use_vehicle, contract_hours_per_day, full_time, hire_date, retire_date)
                               VALUES (?, ?, ?, 1, ?, ?)
                               ON DUPLICATE KEY UPDATE use_vehicle = VALUES(use_vehicle), contract_hours_per_day = VALUES(contract_hours_per_day), hire_date = VALUES(hire_date), retire_date = VALUES(retire_date)');
        $stmt->execute([$targetId, $useV, $contractH, $hireDate, $retireDate]);
    }
    $pdo->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'db error']);
}
