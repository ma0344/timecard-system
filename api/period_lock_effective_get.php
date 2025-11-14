<?php
// api/period_lock_effective_get.php
// User: returns effective locked ranges for current user within optional window.
// Logic: A date is locked if any 'locked' row covers it and there is no later 'reopened' covering or same row reopened.
// Simplified: fetch rows then reduce.

session_start();
header('Content-Type: application/json');
require_once '../db_config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'not logged in']);
    exit;
}
$uid = intval($_SESSION['user_id']);

$start = isset($_GET['start']) ? $_GET['start'] : null;
$end   = isset($_GET['end']) ? $_GET['end'] : null;
if ($start && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $start)) $start = null;
if ($end && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end)) $end = null;
// 安全対策: 月末超過などの不正日付をクランプ
function normalize_ymd($s) {
    if (!$s) return null;
    $y = intval(substr($s, 0, 4));
    $m = intval(substr($s, 5, 2));
    $d = intval(substr($s, 8, 2));
    if ($y <= 0 || $m <= 0 || $m > 12) return null;
    // PHPのcal_days_in_monthは拡張依存のため、DateTimeで安全に求める
    try {
        $dt = DateTime::createFromFormat('!Y-m-d', sprintf('%04d-%02d-01', $y, $m));
        if (!$dt) return null;
        $dt->modify('last day of this month');
        $max = intval($dt->format('d'));
        if ($d < 1) $d = 1;
        if ($d > $max) $d = $max;
        return sprintf('%04d-%02d-%02d', $y, $m, $d);
    } catch (Exception $e) {
        return null;
    }
}
$start = normalize_ymd($start);
$end = normalize_ymd($end);

// Fetch all rows that could overlap window (global or user specific)
$params = [];
$where = ['(user_id IS NULL OR user_id = ?)'];
$params[] = $uid;
if ($start) {
    $where[] = 'end_date >= ?';
    $params[] = $start;
}
if ($end) {
    $where[] = 'start_date <= ?';
    $params[] = $end;
}
$sql = 'SELECT id,user_id,start_date,end_date,status,locked_at,reopened_at FROM attendance_period_locks WHERE ' . implode(' AND ', $where) . ' ORDER BY start_date';

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Build day map (could be optimized; window size typically small ~ 2 months)
    $dayMap = [];
    // Determine actual window bounds if not provided
    $winStart = $start;
    $winEnd = $end;
    if (!$winStart || !$winEnd) {
        foreach ($rows as $r) {
            if (!$winStart || $r['start_date'] < $winStart) $winStart = $r['start_date'];
            if (!$winEnd   || $r['end_date'] > $winEnd) $winEnd = $r['end_date'];
        }
    }
    if ($winStart && $winEnd) {
        $cur = $winStart;
        while ($cur <= $winEnd) {
            $dayMap[$cur] = false; // default unlocked
            $cur = date('Y-m-d', strtotime($cur . ' +1 day'));
        }
    }
    // Apply locks (locked rows set true, reopened rows set false)
    foreach ($rows as $r) {
        $cur = $r['start_date'];
        while ($cur <= $r['end_date']) {
            if (isset($dayMap[$cur])) {
                if ($r['status'] === 'locked') $dayMap[$cur] = true;
                else if ($r['status'] === 'reopened') $dayMap[$cur] = false;
            }
            $cur = date('Y-m-d', strtotime($cur . ' +1 day'));
        }
    }
    // Compress consecutive locked days to ranges
    $ranges = [];
    $runStart = null;
    $prev = null;
    foreach (array_keys($dayMap) as $d) {
        if ($dayMap[$d]) {
            if ($runStart === null) {
                $runStart = $d;
                $prev = $d;
            } else {
                $prev = $d;
            }
        } else {
            if ($runStart !== null) {
                $ranges[] = ['start' => $runStart, 'end' => $prev];
                $runStart = null;
                $prev = null;
            }
        }
    }
    if ($runStart !== null) {
        $ranges[] = ['start' => $runStart, 'end' => $prev];
    }

    echo json_encode(['ok' => true, 'locked_ranges' => $ranges]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'server error']);
}
