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
$sessionUserId = $_SESSION['user_id'];

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
    // トランザクション開始（整合性確保）
    $pdo->beginTransaction();
    // 管理者権限の確認とターゲットユーザーの決定
    $stmt = $pdo->prepare('SELECT role FROM users WHERE id = ?');
    $stmt->execute([$sessionUserId]);
    $me = $stmt->fetch(PDO::FETCH_ASSOC);
    $isAdmin = $me && $me['role'] === 'admin';

    // デフォルトはログインユーザー、自分以外を指定できるのは管理者のみ
    $targetUserId = $sessionUserId;
    if ($isAdmin && isset($data['user_id']) && $data['user_id']) {
        $targetUserId = (int)$data['user_id'];
    }

    // ロック期間中の編集禁止
    $lockChk = $pdo->prepare('SELECT 1 FROM attendance_period_locks WHERE status="locked" AND (user_id IS NULL OR user_id=?) AND start_date <= ? AND end_date >= ? LIMIT 1');
    $lockChk->execute([$targetUserId, $date, $date]);
    if ($lockChk->fetchColumn()) {
        http_response_code(403);
        echo json_encode(['error' => 'locked period']);
        exit;
    }

    // timecards 更新 or 挿入（ハードデリート運用）
    $stmt = $pdo->prepare('SELECT id FROM timecards WHERE user_id = ? AND work_date = ? LIMIT 1');
    $stmt->execute([$targetUserId, $date]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $stmt = $pdo->prepare('UPDATE timecards SET clock_in = ?, clock_out = ?' . ($vehicleDistance !== null ? ', vehicle_distance = ?' : '') . ' WHERE id = ?');
        $params = [$clockInDateTime, $clockOutDateTime];
        if ($vehicleDistance !== null) $params[] = $vehicleDistance;
        $params[] = $row['id'];
        $stmt->execute($params);
        $timecardId = (int)$row['id'];
    } else {
        $stmt = $pdo->prepare('INSERT INTO timecards (user_id, work_date, clock_in, clock_out' . ($vehicleDistance !== null ? ', vehicle_distance' : '') . ') VALUES (?, ?, ?, ?' . ($vehicleDistance !== null ? ', ?' : '') . ')');
        $params = [$targetUserId, $date, $clockInDateTime, $clockOutDateTime];
        if ($vehicleDistance !== null) $params[] = $vehicleDistance;
        $stmt->execute($params);
        $timecardId = (int)$pdo->lastInsertId();
    }
    // 既存休憩は物理削除→再挿入
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
    $pdo->commit();
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
