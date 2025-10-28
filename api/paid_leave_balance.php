<?php
// api/paid_leave_balance.php
session_start();
header('Content-Type: application/json');
require_once '../db_config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'not logged in']);
    exit;
}

// 管理者のみ（現状は管理UIからの利用想定）
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
if (!$targetId) {
    http_response_code(400);
    echo json_encode(['error' => 'user_id required']);
    exit;
}

$stmt = $pdo->prepare('SELECT IFNULL(SUM(grant_hours),0) AS total FROM paid_leaves WHERE user_id = ?');
$stmt->execute([$targetId]);
$grantedTotal = (float)$stmt->fetchColumn();

// 従来の「有効付与合計」は合計grantで計上（従来表示互換）
$stmt = $pdo->prepare('SELECT IFNULL(SUM(grant_hours),0) AS total FROM paid_leaves WHERE user_id = ? AND (expire_date IS NULL OR expire_date > CURDATE())');
$stmt->execute([$targetId]);
$grantedActive = (float)$stmt->fetchColumn();

// 合計利用は USE のみ（EXPIREは除外）
$stmt = $pdo->prepare("SELECT IFNULL(SUM(used_hours),0) AS total FROM paid_leave_logs WHERE user_id = ? AND (log_type_id IS NULL OR log_type_id <> (SELECT id FROM log_types WHERE code = 'EXPIRE' LIMIT 1))");
$stmt->execute([$targetId]);
$usedTotal = (float)$stmt->fetchColumn();

// サマリから現在残を取得（なければ従来計算にフォールバック）
$stmt = $pdo->prepare('SELECT balance_hours FROM user_leave_summary WHERE user_id = ?');
$stmt->execute([$targetId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$balance = $row ? (float)$row['balance_hours'] : ($grantedActive - $usedTotal);

echo json_encode([
    'user_id' => $targetId,
    'granted_total_hours' => $grantedTotal,
    'granted_active_hours' => $grantedActive,
    'used_total_hours' => $usedTotal,
    'balance_hours' => $balance,
    'as_of' => date('Y-m-d'),
]);
