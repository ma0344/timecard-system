<?php
// api/period_lock_set.php
// Admin: lock a period (confirm attendance) for a specific user or all users (user_id null)
// POST JSON: { user_id:null|number, start_date:"YYYY-MM-DD", end_date:"YYYY-MM-DD", note? }

session_start();
header('Content-Type: application/json');
require_once '../db_config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'not logged in']);
    exit;
}
$actor = intval($_SESSION['user_id']);
$roleStmt = $pdo->prepare('SELECT role FROM users WHERE id=?');
$roleStmt->execute([$actor]);
$me = $roleStmt->fetch(PDO::FETCH_ASSOC);
if (!$me || $me['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'forbidden']);
    exit;
}

$in = json_decode(file_get_contents('php://input'), true) ?: [];
$userId = array_key_exists('user_id', $in) ? ($in['user_id'] === null ? null : intval($in['user_id'])) : null; // null = global
$start = $in['start_date'] ?? null;
$end   = $in['end_date'] ?? null;
$note  = isset($in['note']) ? trim(strval($in['note'])) : null;

if (!$start || !$end || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $start) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end)) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid date']);
    exit;
}
if ($start > $end) {
    http_response_code(400);
    echo json_encode(['error' => 'range order']);
    exit;
}

try {
    // Overlap policy: reject if an existing LOCKED range overlaps (same scope) and not reopened.
    $ovSql = 'SELECT id,start_date,end_date,status FROM attendance_period_locks WHERE status="locked" AND ' . (is_null($userId) ? 'user_id IS NULL' : 'user_id = ?') . ' AND NOT (end_date < ? OR start_date > ?) LIMIT 1';
    $ovParams = is_null($userId) ? [$start, $end] : [$userId, $start, $end];
    $ov = $pdo->prepare($ovSql);
    $ov->execute($ovParams);
    $conflict = $ov->fetch(PDO::FETCH_ASSOC);
    if ($conflict) {
        http_response_code(409);
        echo json_encode(['error' => 'overlap_locked', 'conflict' => $conflict]);
        exit;
    }

    $stmt = $pdo->prepare('INSERT INTO attendance_period_locks (user_id,start_date,end_date,status,locked_by,note) VALUES (?,?,?,?,?,?)');
    $stmt->execute([$userId, $start, $end, 'locked', $actor, $note]);
    echo json_encode(['ok' => true, 'id' => $pdo->lastInsertId()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'server error']);
}
