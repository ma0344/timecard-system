<?php
// api/timecard_status.php
session_start();
header('Content-Type: application/json');
require_once '../db_config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'not logged in']);
    exit;
}
$userId = $_SESSION['user_id'];
$today = date('Y-m-d');

$stmt = $pdo->prepare('SELECT id, clock_in, clock_out FROM timecards WHERE user_id = ? AND work_date = ?');
$stmt->execute([$userId, $today]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

$onBreak = false;
$breakStartTime = null;
$breakEndTime = null;
if ($row && $row['id']) {
    // 休憩開始・終了の最新を取得
    $stmt2 = $pdo->prepare('SELECT break_start, break_end FROM breaks WHERE timecard_id = ? ORDER BY break_start DESC LIMIT 1');
    $stmt2->execute([$row['id']]);
    $break = $stmt2->fetch(PDO::FETCH_ASSOC);
    if ($break) {
        if ($break['break_start']) $breakStartTime = $break['break_start'];
        if ($break['break_end']) $breakEndTime = $break['break_end'];
    }
    // onBreakも最新レコードで判定
    $onBreak = ($break && $break['break_start'] && !$break['break_end']);
}

echo json_encode([
    'clockedIn' => (bool)($row && $row['clock_in']),
    'clockedOut' => (bool)($row && $row['clock_out']),
    'onBreak' => $onBreak,
    'clockInTime' => $row['clock_in'] ?? null,
    'clockOutTime' => $row['clock_out'] ?? null,
    'breakStartTime' => $breakStartTime,
    'breakEndTime' => $breakEndTime
]);
