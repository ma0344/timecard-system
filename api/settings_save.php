<?php
// api/settings_save.php
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
} // 入力値取得
$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid input']);
    exit;
}

// バリデーション（簡易）
$period_start = (int)$data['period_start'];
$period_end = (int)$data['period_end'];
$rounding_type = $data['rounding_type'];
$rounding_unit = (int)$data['rounding_unit'];
$work_hours = (int)$data['work_hours'];
$work_minutes = (int)$data['work_minutes'];
if ($period_start < 1 || $period_start > 31 || $period_end < 1 || $period_end > 31) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid period']);
    exit;
}
if (!in_array($rounding_type, ['floor', 'ceil', 'round'])) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid rounding_type']);
    exit;
}
if (!in_array($rounding_unit, [1, 5, 10, 15])) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid rounding_unit']);
    exit;
}
if ($work_hours < 0 || $work_hours > 24 || $work_minutes < 0 || $work_minutes > 59) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid work time']);
    exit;
}

// 設定は常に1レコードのみとし、REPLACEで保存
$sql = "REPLACE INTO settings (id, period_start, period_end, rounding_type, rounding_unit, work_hours, work_minutes, updated_at) VALUES (1,?,?,?,?,?,?,NOW())";
$stmt = $pdo->prepare($sql);
$result = $stmt->execute([
    $period_start,
    $period_end,
    $rounding_type,
    $rounding_unit,
    $work_hours,
    $work_minutes
]);
if ($result) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'db error']);
}
