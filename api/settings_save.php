<?php
/*
 * 目的: 勤怠設定値（期間・丸め・法定時間等）の保存を行います。
 * 入力: 設定のJSON（管理者権限）
 * 出力: 保存結果（成功/失敗）
 */
?>
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
$legal_hours_28 = isset($data['legal_hours_28']) ? (int)$data['legal_hours_28'] : null;
$legal_hours_29 = isset($data['legal_hours_29']) ? (int)$data['legal_hours_29'] : null;
$legal_hours_30 = isset($data['legal_hours_30']) ? (int)$data['legal_hours_30'] : null;
$legal_hours_31 = isset($data['legal_hours_31']) ? (int)$data['legal_hours_31'] : null;
$paid_leave_valid_months = isset($data['paid_leave_valid_months']) ? (int)$data['paid_leave_valid_months'] : 24;
$paid_leave_rules_raw = isset($data['paid_leave_rules']) ? $data['paid_leave_rules'] : null; // JSON文字列 or 配列
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

// Legal monthly hours quick sanity (allow null -> will fallback client-side)
foreach ([28 => $legal_hours_28, 29 => $legal_hours_29, 30 => $legal_hours_30, 31 => $legal_hours_31] as $d => $v) {
  if ($v !== null && ($v < 0 || $v > 300)) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid legal hours for ' . $d . ' days']);
    exit;
  }
}

if ($paid_leave_valid_months < 0 || $paid_leave_valid_months > 120) {
  http_response_code(400);
  echo json_encode(['error' => 'invalid paid_leave_valid_months']);
  exit;
}

// Normalize paid_leave_rules as JSON string
if (is_array($paid_leave_rules_raw)) {
  $paid_leave_rules = json_encode($paid_leave_rules_raw, JSON_UNESCAPED_UNICODE);
} else if (is_string($paid_leave_rules_raw) && $paid_leave_rules_raw !== '') {
  // validate JSON
  json_decode($paid_leave_rules_raw, true);
  if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['error' => 'paid_leave_rules must be valid JSON']);
    exit;
  }
  $paid_leave_rules = $paid_leave_rules_raw;
} else {
  // default rules
  $paid_leave_rules = json_encode([
    'milestones' => ['6m', '1y6m', '2y6m', '3y6m', '4y6m', '5y6m', '6y6m+'],
    'fulltime' => [10, 11, 12, 14, 16, 18, 20],
    'parttime' => [
      '4d' => [7, 8, 9, 10, 12, 13, 15],
      '3d' => [5, 6, 6, 8, 9, 10, 11],
      '2d' => [3, 4, 4, 5, 6, 6, 7],
      '1d' => [1, 2, 2, 2, 3, 3, 3]
    ]
  ], JSON_UNESCAPED_UNICODE);
}

// 設定は常に1レコードのみとし、REPLACEで保存
$sql = "REPLACE INTO settings (id, period_start, period_end, rounding_type, rounding_unit, work_hours, work_minutes, legal_hours_28, legal_hours_29, legal_hours_30, legal_hours_31, paid_leave_valid_months, paid_leave_rules, updated_at) VALUES (1,?,?,?,?,?,?,?,?,?,?,?,?,NOW())";
$stmt = $pdo->prepare($sql);
$result = $stmt->execute([
  $period_start,
  $period_end,
  $rounding_type,
  $rounding_unit,
  $work_hours,
  $work_minutes,
  $legal_hours_28,
  $legal_hours_29,
  $legal_hours_30,
  $legal_hours_31,
  $paid_leave_valid_months,
  $paid_leave_rules
]);
if ($result) {
  echo json_encode(['success' => true]);
} else {
  http_response_code(500);
  echo json_encode(['error' => 'db error']);
}
