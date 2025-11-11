<?php
// api/day_status_self_clear.php
// 自分自身の当日の日ステータス上書きを取り消す（一般ユーザー可）
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

$today = date('Y-m-d');
if ($date !== $today) {
    http_response_code(403);
    echo json_encode(['error' => 'only today allowed']);
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
