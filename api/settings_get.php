<?php
// api/settings_get.php
session_start();
header('Content-Type: application/json');
require_once '../db_config.php';

// 管理者権限チェック
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'not logged in']);
    exit;
}
$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare('SELECT role FROM users WHERE id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user || $user['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'forbidden']);
    exit;
}
// 設定取得
$sql = "SELECT period_start, period_end, rounding_type, rounding_unit, work_hours, work_minutes, legal_hours_28, legal_hours_29, legal_hours_30, legal_hours_31, paid_leave_valid_months, paid_leave_rules FROM settings ORDER BY id DESC LIMIT 1";
$stmt = $pdo->query($sql);
if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // そのまま返す（paid_leave_rules は JSON カラム -> 文字列として返る）
    echo json_encode($row);
} else {
    // デフォルト値
    echo json_encode([
        'period_start' => 1,
        'period_end' => 31,
        'rounding_type' => 'floor',
        'rounding_unit' => 1,
        'work_hours' => 8,
        'work_minutes' => 0,
        'legal_hours_28' => 160,
        'legal_hours_29' => 165,
        'legal_hours_30' => 171,
        'legal_hours_31' => 177,
        'paid_leave_valid_months' => 24,
        'paid_leave_rules' => json_encode([
            'milestones' => ['6m', '1y6m', '2y6m', '3y6m', '4y6m', '5y6m', '6y6m+'],
            'fulltime' => [10, 11, 12, 14, 16, 18, 20],
            'parttime' => [
                '4d' => [7, 8, 9, 10, 12, 13, 15],
                '3d' => [5, 6, 6, 8, 9, 10, 11],
                '2d' => [3, 4, 4, 5, 6, 6, 7],
                '1d' => [1, 2, 2, 2, 3, 3, 3]
            ]
        ])
    ]);
    // settingsテーブルにレコードがない場合の初期値
}
