<?php
// api/today_status_summary.php
// 管理者向け: 本日の全ユーザーの実績ステータスを要約して返す
// status: no_record | working | on_break | done

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

try {
    // 可視ユーザーのみ対象
    // timecards: 今日の日付のみ結合
    // breaks: 休憩中判定のため、break_end が NULL のものが存在するか
    $sql = "
        SELECT
            u.id AS user_id,
            u.name AS name,
            tc.id AS timecard_id,
            tc.clock_in,
            tc.clock_out,
            (
                SELECT MAX(b.break_start)
                FROM breaks b
                WHERE b.timecard_id = tc.id AND b.break_end IS NULL
            ) AS on_break_since
        FROM users u
        LEFT JOIN timecards tc
            ON tc.user_id = u.id AND tc.work_date = CURDATE()
        WHERE u.visible = 1
        ORDER BY u.id
    ";
    $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

    $items = [];
    foreach ($rows as $r) {
        $status = 'no_record';
        if (!is_null($r['timecard_id'])) {
            $ci = $r['clock_in'];
            $co = $r['clock_out'];
            $onBreakSince = $r['on_break_since'];
            if (!empty($co)) {
                $status = 'done';
            } else if (!empty($onBreakSince)) {
                $status = 'on_break';
            } else if (!empty($ci) && empty($co)) {
                $status = 'working';
            } else {
                // timecardはあるが入退勤が空の場合
                $status = 'no_record';
            }
        }
        $items[] = [
            'user_id' => (int)$r['user_id'],
            'name' => $r['name'],
            'status' => $status,
            'clock_in' => $r['clock_in'],
            'clock_out' => $r['clock_out'],
        ];
    }

    echo json_encode(['ok' => true, 'items' => $items]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'server error']);
}
