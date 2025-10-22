<?php
// api/paid_leave_use.php
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
$usedDate = $data['used_date'] ?? null;
$usedHours = isset($data['used_hours']) ? (float)$data['used_hours'] : null;
$reason = isset($data['reason']) ? trim($data['reason']) : null;

if (!$targetId || !$usedDate || $usedHours === null) {
    http_response_code(400);
    echo json_encode(['error' => 'user_id, used_date, used_hours required']);
    exit;
}
if (!is_numeric($usedHours) || $usedHours <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'used_hours must be > 0']);
    exit;
}

// マイナス残高許可の取得（未設定なら0）
$stmt = $pdo->prepare('SELECT COALESCE(negative_balance_allowed, 0) AS allow_negative FROM user_leave_settings WHERE user_id = ?');
$stmt->execute([$targetId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$allowNegative = $row ? ((int)$row['allow_negative'] === 1) : false;

// 現残高をチェック（有効付与合計 - 利用合計）
$stmt = $pdo->prepare('SELECT IFNULL(SUM(grant_hours),0) FROM paid_leaves WHERE user_id = ? AND (expire_date IS NULL OR expire_date >= CURDATE())');
$stmt->execute([$targetId]);
$activeGranted = (float)$stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT IFNULL(SUM(used_hours),0) FROM paid_leave_logs WHERE user_id = ?');
$stmt->execute([$targetId]);
$usedTotal = (float)$stmt->fetchColumn();

$balance = $activeGranted - $usedTotal;
if (!$allowNegative && $usedHours > $balance + 1e-9) {
    http_response_code(400);
    echo json_encode(['error' => 'insufficient balance']);
    exit;
}

try {
    // ログタイプ（USE）を取得
    $logTypeId = null;
    $stmt = $pdo->prepare("SELECT id FROM log_types WHERE code = 'USE' LIMIT 1");
    if ($stmt->execute()) {
        $logTypeId = $stmt->fetchColumn();
    }
    if (!$logTypeId) {
        $logTypeId = 2;
    } // フォールバック

    $stmt = $pdo->prepare('INSERT INTO paid_leave_logs (user_id, paid_leave_id, used_date, used_hours, reason, log_type_id) VALUES (?, NULL, ?, ?, ?, ?)');
    $stmt->execute([$targetId, $usedDate, $usedHours, $reason, $logTypeId]);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'db error']);
}
