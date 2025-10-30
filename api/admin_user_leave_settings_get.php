<?php
// api/admin_user_leave_settings_get.php
session_start();
header('Content-Type: application/json');
require_once '../db_config.php';

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

$targetId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
if (!$targetId) {
    http_response_code(400);
    echo json_encode(['error' => 'user_id required']);
    exit;
}

// 設定取得（無ければNULL）。有効な基準時間（overrideがNULLならuser_detailの契約時間）も返す
$sql = 'SELECT 
            s.user_id,
            s.default_unit_id,
            s.allow_half_day,
            s.allow_hourly,
            s.base_hours_per_day_override,
            s.carryover_months,
            s.carryover_max_minutes,
            s.negative_balance_allowed,
            s.default_paid_leave_type_id,
            d.contract_hours_per_day
        FROM user_detail d
        LEFT JOIN user_leave_settings s ON s.user_id = d.user_id
        WHERE d.user_id = ?';
$stmt = $pdo->prepare($sql);
$stmt->execute([$targetId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    // user_detail がまだ無いケースに備える（最小限のデフォルト; 契約時間は未設定/null）
    $row = [
        'user_id' => $targetId,
        'default_unit_id' => 1, // DAY
        'allow_half_day' => 1,
        'allow_hourly' => 1,
        'base_hours_per_day_override' => null,
        'carryover_months' => 0,
        'carryover_max_minutes' => 0,
        'negative_balance_allowed' => 0,
        'default_paid_leave_type_id' => 1, // ANNUAL
        'contract_hours_per_day' => null,
    ];
}

$effectiveBaseHours = null;
if (isset($row['base_hours_per_day_override']) && $row['base_hours_per_day_override'] !== null) {
    $effectiveBaseHours = (float)$row['base_hours_per_day_override'];
} elseif (isset($row['contract_hours_per_day']) && $row['contract_hours_per_day'] !== null) {
    $effectiveBaseHours = (float)$row['contract_hours_per_day'];
}
$hasBase = ($effectiveBaseHours !== null && $effectiveBaseHours > 0);

echo json_encode([
    'user_id' => (int)$row['user_id'],
    'default_unit_id' => isset($row['default_unit_id']) ? (int)$row['default_unit_id'] : 1,
    'allow_half_day' => isset($row['allow_half_day']) ? (int)$row['allow_half_day'] : 1,
    'allow_hourly' => isset($row['allow_hourly']) ? (int)$row['allow_hourly'] : 1,
    'base_hours_per_day_override' => $row['base_hours_per_day_override'] !== null ? (float)$row['base_hours_per_day_override'] : null,
    'carryover_months' => isset($row['carryover_months']) ? (int)$row['carryover_months'] : 0,
    'carryover_max_minutes' => isset($row['carryover_max_minutes']) ? (int)$row['carryover_max_minutes'] : 0,
    'negative_balance_allowed' => isset($row['negative_balance_allowed']) ? (int)$row['negative_balance_allowed'] : 0,
    'default_paid_leave_type_id' => isset($row['default_paid_leave_type_id']) ? (int)$row['default_paid_leave_type_id'] : 1,
    'base_hours_per_day_effective' => $hasBase ? $effectiveBaseHours : null,
    'contract_hours_per_day' => isset($row['contract_hours_per_day']) ? (($row['contract_hours_per_day'] !== null) ? (float)$row['contract_hours_per_day'] : null) : null,
    'has_base_hours' => $hasBase ? 1 : 0,
]);
