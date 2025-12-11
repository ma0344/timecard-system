<?php
/*
 * 目的: 管理者がユーザー別の有給設定を更新します。
 * 入力: user_id、設定JSON
 * 出力: 更新結果
 */
?>
<?php
// api/admin_user_leave_settings_update.php
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

$data = json_decode(file_get_contents('php://input'), true);
$targetId = $data['user_id'] ?? null;
if (!$targetId) {
  http_response_code(400);
  echo json_encode(['error' => 'user_id required']);
  exit;
}

// 既存値取得
$stmt = $pdo->prepare('SELECT * FROM user_leave_settings WHERE user_id = ?');
$stmt->execute([$targetId]);
$current = $stmt->fetch(PDO::FETCH_ASSOC);

// 入力が存在する項目のみ反映（存在しなければ現状維持）
$fields = [
  'default_unit_id',
  'allow_half_day',
  'allow_hourly',
  'base_hours_per_day_override',
  'carryover_months',
  'carryover_max_minutes',
  'negative_balance_allowed',
  'default_paid_leave_type_id',
];

$values = [];
foreach ($fields as $f) {
  if (array_key_exists($f, $data)) {
    $values[$f] = $data[$f]; // nullも許容
  } else {
    $values[$f] = $current[$f] ?? null;
  }
}

// null/型の正規化（最低限）
if (isset($values['default_unit_id'])) $values['default_unit_id'] = $values['default_unit_id'] !== null ? (int)$values['default_unit_id'] : null;
if (isset($values['allow_half_day'])) $values['allow_half_day'] = $values['allow_half_day'] !== null ? (int)$values['allow_half_day'] : null;
if (isset($values['allow_hourly'])) $values['allow_hourly'] = $values['allow_hourly'] !== null ? (int)$values['allow_hourly'] : null;
if (isset($values['base_hours_per_day_override'])) $values['base_hours_per_day_override'] = $values['base_hours_per_day_override'] !== null ? (float)$values['base_hours_per_day_override'] : null;
if (isset($values['carryover_months'])) $values['carryover_months'] = $values['carryover_months'] !== null ? (int)$values['carryover_months'] : null;
if (isset($values['carryover_max_minutes'])) $values['carryover_max_minutes'] = $values['carryover_max_minutes'] !== null ? (int)$values['carryover_max_minutes'] : null;
if (isset($values['negative_balance_allowed'])) $values['negative_balance_allowed'] = $values['negative_balance_allowed'] !== null ? (int)$values['negative_balance_allowed'] : null;
if (isset($values['default_paid_leave_type_id'])) $values['default_paid_leave_type_id'] = $values['default_paid_leave_type_id'] !== null ? (int)$values['default_paid_leave_type_id'] : null;

try {
  $stmt = $pdo->prepare('INSERT INTO user_leave_settings (
            user_id, default_unit_id, allow_half_day, allow_hourly,
            base_hours_per_day_override, carryover_months, carryover_max_minutes,
            negative_balance_allowed, default_paid_leave_type_id
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            default_unit_id = VALUES(default_unit_id),
            allow_half_day = VALUES(allow_half_day),
            allow_hourly = VALUES(allow_hourly),
            base_hours_per_day_override = VALUES(base_hours_per_day_override),
            carryover_months = VALUES(carryover_months),
            carryover_max_minutes = VALUES(carryover_max_minutes),
            negative_balance_allowed = VALUES(negative_balance_allowed),
            default_paid_leave_type_id = VALUES(default_paid_leave_type_id)');
  $stmt->execute([
    $targetId,
    $values['default_unit_id'],
    $values['allow_half_day'],
    $values['allow_hourly'],
    $values['base_hours_per_day_override'],
    $values['carryover_months'],
    $values['carryover_max_minutes'],
    $values['negative_balance_allowed'],
    $values['default_paid_leave_type_id'],
  ]);
  echo json_encode(['success' => true]);
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(['error' => 'db error']);
}
