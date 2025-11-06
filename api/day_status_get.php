<?php
// api/day_status_get.php
session_start();
header('Content-Type: application/json');
require_once '../db_config.php';

// Admin check
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'not logged in']);
    exit;
}
$adminId = $_SESSION['user_id'];
$stmt = $pdo->prepare('SELECT role FROM users WHERE id = ?');
$stmt->execute([$adminId]);
$me = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$me || $me['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'forbidden']);
    exit;
}

$userId = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;
$start = isset($_GET['start']) ? $_GET['start'] : date('Y-m-d');
$end   = isset($_GET['end']) ? $_GET['end'] : $start;

// Basic validation
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end)) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid date']);
    exit;
}

try {
    if ($userId) {
        $sql = "SELECT user_id, date, status, note, created_by, created_at, revoked_at
                FROM day_status_overrides
                WHERE user_id = ? AND date BETWEEN ? AND ? AND revoked_at IS NULL
                ORDER BY date";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId, $start, $end]);
    } else {
        $sql = "SELECT user_id, date, status, note, created_by, created_at, revoked_at
                FROM day_status_overrides
                WHERE date BETWEEN ? AND ? AND revoked_at IS NULL
                ORDER BY user_id, date";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$start, $end]);
    }
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['ok' => true, 'items' => $rows]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'server error']);
}
