<?php
/*
 * 目的: マスタデータ（各種選択肢/種別）を取得します。
 * 入力: 必要に応じてカテゴリ指定
 * 出力: マスタ一覧（コード/名称 等）
 */
?>
<?php
/*
 * 目的: マスターデータ（部署、役職、区分など）を取得します。
 * 入力: 種別の指定（任意）
 * 出力: マスター一覧
 */
?>
<?php
// api/masters_get.php
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

// マスター取得
$leaveUnits = $pdo->query("SELECT id, code, name, minutes_per_unit FROM leave_units WHERE is_active = 1 ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
$paidLeaveTypes = $pdo->query("SELECT id, code, name, default_unit_id FROM paid_leave_types WHERE is_active = 1 ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
$sourceTypes = $pdo->query("SELECT id, code, name, paid_leave_type_id FROM source_types WHERE is_active = 1 ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
  'leave_units' => $leaveUnits,
  'paid_leave_types' => $paidLeaveTypes,
  'source_types' => $sourceTypes,
]);
