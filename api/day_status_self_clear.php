<?php
// api/day_status_self_clear.php
// 自分自身の日ステータス上書きを取り消す（一般ユーザー可）
session_start();
header('Content-Type: application/json');
require_once '../db_config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'not logged in']);
    exit;
}
$userId = intval($_SESSION['user_id']);

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$date = isset($input['date']) ? $input['date'] : null;
if (!$date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid input']);
    exit;
}

// 期間ロック中は変更不可
function is_locked($pdo, $userId, $date) {
    $q = $pdo->prepare('SELECT 1 FROM attendance_period_locks WHERE status="locked" AND (user_id IS NULL OR user_id=?) AND start_date <= ? AND end_date >= ? LIMIT 1');
    $q->execute([$userId, $date, $date]);
    return (bool)$q->fetchColumn();
}
if (is_locked($pdo, $userId, $date)) {
    http_response_code(403);
    echo json_encode(['error' => 'locked period']);
    exit;
}

try {
    $stmt = $pdo->prepare('UPDATE day_status_overrides SET revoked_at = NOW() WHERE user_id = ? AND date = ? AND revoked_at IS NULL');
    $stmt->execute([$userId, $date]);
    echo json_encode(['ok' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'server error']);
}
