<?php
// api/today_events.php
session_start();
header('Content-Type: application/json');
require_once '../db_config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'not logged in']);
    exit;
}

$userId = intval($_SESSION['user_id']);
$today = date('Y-m-d');

try {
    // 当日のタイムカード取得
    $stmt = $pdo->prepare('SELECT id, clock_in, clock_out FROM timecards WHERE user_id = ? AND work_date = ?');
    $stmt->execute([$userId, $today]);
    $tc = $stmt->fetch(PDO::FETCH_ASSOC);

    $items = [];
    if ($tc) {
        if (!empty($tc['clock_in'])) {
            $items[] = ['type' => 'clock_in', 'time' => $tc['clock_in'], 'label' => '出勤'];
        }
        // 休憩イベント（開始→終了）
        $stmt2 = $pdo->prepare('SELECT break_start, break_end FROM breaks WHERE timecard_id = ? ORDER BY break_start ASC');
        $stmt2->execute([$tc['id']]);
        while ($br = $stmt2->fetch(PDO::FETCH_ASSOC)) {
            if (!empty($br['break_start'])) {
                $items[] = ['type' => 'break_start', 'time' => $br['break_start'], 'label' => '休憩開始'];
            }
            if (!empty($br['break_end'])) {
                $items[] = ['type' => 'break_end', 'time' => $br['break_end'], 'label' => '休憩終了'];
            }
        }
        if (!empty($tc['clock_out'])) {
            $items[] = ['type' => 'clock_out', 'time' => $tc['clock_out'], 'label' => '退勤'];
        }

        // 念のため時刻でソート（同時刻は安定）
        usort($items, function ($a, $b) {
            if ($a['time'] === $b['time']) return 0;
            return strcmp($a['time'], $b['time']);
        });
    }

    echo json_encode(['date' => $today, 'items' => $items]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'DBエラー', 'message' => $e->getMessage()]);
}
