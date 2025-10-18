<?php
// api/attendance_update.php
session_start();
header('Content-Type: application/json');
require_once '../db_config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'not logged in']);
    exit;
}
$userId = $_SESSION['user_id'];

$data = json_decode(file_get_contents('php://input'), true);
$date = $data['date'] ?? '';
$clockIn = $data['clockIn'] ?? '';
$clockOut = $data['clockOut'] ?? '';
// clockIn, clockOutが空でなければ、日付と結合してDATETIME形式に
$clockInDateTime = $clockIn ? toDateTime($date, $clockIn) : null;
$clockOutDateTime = $clockOut ? toDateTime($date, $clockOut) : null;
$breaks = $data['breaks'] ?? [];
$vehicleDistance = isset($data['vehicleDistance']) ? intval($data['vehicleDistance']) : null;

if (!$date) {
    http_response_code(400);
    echo json_encode(['error' => 'date required']);
    exit;
}

function toDateTime($date, $time) {
    // すでに日付が含まれていればそのまま返す
    if (preg_match('/^\\d{4}-\\d{2}-\\d{2} /', $time)) {
        return $time;
    }
    // HH:MMまたはHH:MM:SS形式なら$dateと連結
    return $date . ' ' . (strlen($time) === 5 ? $time . ':00' : $time);
}

try {
    // timecards更新 or 挿入
    $stmt = $pdo->prepare('SELECT id FROM timecards WHERE user_id = ? AND work_date = ?');
    $stmt->execute([$userId, $date]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $stmt = $pdo->prepare('UPDATE timecards SET clock_in = ?, clock_out = ?' . ($vehicleDistance !== null ? ', vehicle_distance = ?' : '') . ' WHERE id = ?');
        $params = [$clockInDateTime, $clockOutDateTime];
        if ($vehicleDistance !== null) $params[] = $vehicleDistance;
        $params[] = $row['id'];
        $stmt->execute($params);
        $timecardId = $row['id'];
    } else {
        $stmt = $pdo->prepare('INSERT INTO timecards (user_id, work_date, clock_in, clock_out' . ($vehicleDistance !== null ? ', vehicle_distance' : '') . ') VALUES (?, ?, ?, ?' . ($vehicleDistance !== null ? ', ?' : '') . ')');
        $params = [$userId, $date, $clockInDateTime, $clockOutDateTime];
        if ($vehicleDistance !== null) $params[] = $vehicleDistance;
        $stmt->execute($params);
        $timecardId = $pdo->lastInsertId();
    }
    // breaks全削除→再挿入
    $stmt = $pdo->prepare('DELETE FROM breaks WHERE timecard_id = ?');
    $stmt->execute([$timecardId]);
    foreach ($breaks as $b) {
        if (!empty($b['start']) && !empty($b['end'])) {
            $breakStartDateTime = toDateTime($date, $b['start']);
            $breakEndDateTime = toDateTime($date, $b['end']);
            $stmt = $pdo->prepare('INSERT INTO breaks (timecard_id, break_start, break_end) VALUES (?, ?, ?)');
            $stmt->execute([$timecardId, $breakStartDateTime, $breakEndDateTime]);
        }
    }
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
