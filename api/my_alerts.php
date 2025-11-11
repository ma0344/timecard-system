<?php
// api/my_alerts.php
// ログインユーザー自身のアラート（未退勤・未打刻）を返す
session_start();
header('Content-Type: application/json');
require_once '../db_config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'not logged in']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$days = isset($_GET['days']) ? max(1, min(30, (int)$_GET['days'])) : 5; // 過去N日（当日除く）

try {
    // 対象日範囲
    $totalDays = (int)$days;
    $rangeStartDt = (new DateTime('today'))->modify('-' . $totalDays . ' day');
    $rangeEndDt = new DateTime('yesterday');
    $rangeStart = $rangeStartDt->format('Y-m-d');
    $rangeEnd = $rangeEndDt->format('Y-m-d');

    // 1) 過去日の未退勤（当日除外）
    $sqlIncomplete = "SELECT work_date FROM timecards WHERE user_id = ? AND work_date < CURDATE() AND work_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY) AND clock_in IS NOT NULL AND clock_out IS NULL ORDER BY work_date DESC";
    $st1 = $pdo->prepare($sqlIncomplete);
    $st1->execute([$userId, $totalDays]);
    $pastIncomplete = $st1->fetchAll(PDO::FETCH_COLUMN) ?: [];

    // 2) 未打刻（範囲内で出勤記録がなく、かつ全休/無視の有効日がない）
    // day_status_effective を参照し、半休は除外対象に含めない
    $excludeStatuses = "('off','ignore')";
    // 出勤のある日を取得
    $presentDays = [];
    $stP = $pdo->prepare('SELECT work_date FROM timecards WHERE user_id = ? AND work_date BETWEEN ? AND ?');
    $stP->execute([$userId, $rangeStart, $rangeEnd]);
    while ($r = $stP->fetch(PDO::FETCH_ASSOC)) {
        $presentDays[$r['work_date']] = true;
    }
    // 除外日を取得（全休/無視）
    $excludeDays = [];
    $stE = $pdo->prepare("SELECT date FROM day_status_effective WHERE user_id = ? AND status IN $excludeStatuses AND date BETWEEN ? AND ?");
    $stE->execute([$userId, $rangeStart, $rangeEnd]);
    while ($r = $stE->fetch(PDO::FETCH_ASSOC)) {
        $excludeDays[$r['date']] = true;
    }

    // 範囲内日付列挙して未打刻を抽出（降順）
    $missingDates = [];
    $cursor = new DateTime($rangeStart);
    $endDt = new DateTime($rangeEnd);
    while ($cursor <= $endDt) {
        $d = $cursor->format('Y-m-d');
        if (empty($presentDays[$d]) && empty($excludeDays[$d])) {
            $missingDates[] = $d;
        }
        $cursor->modify('+1 day');
    }
    rsort($missingDates);

    echo json_encode([
        'ok' => true,
        'items' => [
            'past_incomplete' => array_map(function ($d) {
                return ['work_date' => $d];
            }, $pastIncomplete),
            'missing_dates'   => $missingDates,
            'workday_range'   => ['days' => $days, 'range_start' => $rangeStart, 'range_end' => $rangeEnd]
        ]
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    error_log('api/my_alerts.php error: ' . $e->getMessage());
    echo json_encode(['error' => 'db error']);
}
