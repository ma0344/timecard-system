<?php
// api/attendance_list.php

session_start();
header('Content-Type: application/json');
require_once '../db_config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'not logged in']);
    exit;
}
$userId = $_SESSION['user_id'];
// ロール取得（存在しない場合は一般扱い）
$stmt = $pdo->prepare('SELECT role FROM users WHERE id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$isAdmin = $user && $user['role'] === 'admin';

// 入力受け取り
$data = json_decode(file_get_contents('php://input'), true);
$targetUserId = $data['user_id'] ?? ($_GET['user_id'] ?? null);
// 一般ユーザーは user_id を強制的に自分に固定
if (!$isAdmin) {
    $targetUserId = $userId;
}
$start = $data['start'] ?? ($_GET['start'] ?? null);
$end = $data['end'] ?? ($_GET['end'] ?? null);

if ($start && $end) {
    $uid = $targetUserId ?: $userId;
    $stmt = $pdo->prepare('SELECT id, work_date, clock_in, clock_out, clock_in_manual, clock_out_manual, vehicle_distance FROM timecards WHERE user_id = ? AND work_date BETWEEN ? AND ? ORDER BY work_date');
    $stmt->execute([$uid, $start, $end]);
} else {
    $uid = $targetUserId ?: $userId;
    $stmt = $pdo->prepare('SELECT id, work_date, clock_in, clock_out, clock_in_manual, clock_out_manual, vehicle_distance FROM timecards WHERE user_id = ? AND work_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) ORDER BY work_date DESC');
    $stmt->execute([$uid]);
}
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$result = [];
foreach ($rows as $row) {
    $breaks = [];
    if ($row['id']) {
        $stmt2 = $pdo->prepare('SELECT break_start, break_end, break_start_manual, break_end_manual FROM breaks WHERE timecard_id = ? ORDER BY break_start');
        $stmt2->execute([$row['id']]);
        $breaks = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    }
    $result[] = [
        'date' => $row['work_date'],
        'clockIn' => $row['clock_in'],
        'clockOut' => $row['clock_out'],
        'clockInManual' => isset($row['clock_in_manual']) ? (int)$row['clock_in_manual'] : 0,
        'clockOutManual' => isset($row['clock_out_manual']) ? (int)$row['clock_out_manual'] : 0,
        'vehicleDistance' => isset($row['vehicle_distance']) ? (int)$row['vehicle_distance'] : 0,
        'breaks' => array_map(function ($b) {
            return [
                'start' => $b['break_start'],
                'end' => $b['break_end'],
                'startManual' => isset($b['break_start_manual']) ? (int)$b['break_start_manual'] : 0,
                'endManual' => isset($b['break_end_manual']) ? (int)$b['break_end_manual'] : 0
            ];
        }, $breaks)
    ];
}
echo json_encode($result);
