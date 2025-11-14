<?php
// api/admin_user_legacy_halfday_list.php
// パートユーザーで半休(am_off/pm_off) が存在するユーザーを期間内で検知して一覧化
session_start();
header('Content-Type: application/json');
require_once '../db_config.php';

// 権限チェック
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'not logged in']);
    exit;
}
$uid = intval($_SESSION['user_id']);
$stmt = $pdo->prepare('SELECT role FROM users WHERE id = ?');
$stmt->execute([$uid]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user || $user['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'forbidden']);
    exit;
}

// 期間
$start = isset($_GET['start']) ? $_GET['start'] : null;
$end   = isset($_GET['end'])   ? $_GET['end']   : null;

// 日付バリデーション
$validDate = function ($s) {
    return (bool)preg_match('/^\d{4}-\d{2}-\d{2}$/', $s);
};
if (!$start || !$validDate($start)) {
    // 既定: 今日から遡って 180 日
    $end = $end && $validDate($end) ? $end : date('Y-m-d');
    $tsEnd = strtotime($end);
    $start = date('Y-m-d', strtotime('-180 days', $tsEnd));
} else {
    $end = $end && $validDate($end) ? $end : date('Y-m-d');
}

try {
    $sql = "SELECT u.id, u.name, COALESCE(d.full_time,1) AS full_time,
                   MIN(e.date) AS first_date, MAX(e.date) AS last_date,
                   COUNT(*) AS half_count
            FROM day_status_effective e
            JOIN users u ON u.id = e.user_id
            LEFT JOIN user_detail d ON d.user_id = u.id
            WHERE COALESCE(d.full_time,1) = 0
              AND e.status IN ('am_off','pm_off')
              AND e.date BETWEEN ? AND ?
            GROUP BY u.id, u.name, COALESCE(d.full_time,1)
            ORDER BY half_count DESC, u.id";
    $st = $pdo->prepare($sql);
    $st->execute([$start, $end]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['start' => $start, 'end' => $end, 'items' => $rows]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'server error']);
}
