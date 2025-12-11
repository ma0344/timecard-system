<?php
/*
 * 目的: 管理者がユーザーを論理/物理削除します。
 * 入力: user_id
 * 出力: 削除結果（成功/失敗）
 */
?>
<?php
// api/admin_user_delete.php
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
// POSTでユーザーID・退職日を受け取る
$data = json_decode(file_get_contents('php://input'), true);
$targetId = $data['id'] ?? null;
$retire_date = array_key_exists('retire_date', $data) ? $data['retire_date'] : null;
if (!$targetId) {
  http_response_code(400);
  echo json_encode(['error' => 'id required']);
  exit;
}
// 退職日は必須
if ($retire_date === null || $retire_date === '') {
  http_response_code(400);
  echo json_encode(['error' => 'retire_date required']);
  exit;
}
// visible=0に更新（重複回避のため、名前末尾に削除タイムスタンプを付与）
try {
  $pdo->beginTransaction();
  // 現在の名前を取得し、末尾に削除情報を付与
  $stmt = $pdo->prepare('SELECT name FROM users WHERE id = ? FOR UPDATE');
  $stmt->execute([$targetId]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$row) {
    throw new Exception('not found');
  }
  $currentName = $row['name'];
  $suffix = ' (deleted ' . date('Ymd-His') . '#' . (int)$targetId . ')';
  $newName = $currentName . $suffix;
  // users を非表示かつ名前を更新
  $stmt = $pdo->prepare('UPDATE users SET visible = 0, name = ? WHERE id = ?');
  $stmt->execute([$newName, $targetId]);

  // user_detail に退職日を保存（他の値は既存を維持しつつ upsert）
  $stmt = $pdo->prepare('SELECT use_vehicle, contract_hours_per_day, full_time, hire_date FROM user_detail WHERE user_id = ?');
  $stmt->execute([$targetId]);
  $detail = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
  $useV = isset($detail['use_vehicle']) ? (int)$detail['use_vehicle'] : 1;
  $contractH = isset($detail['contract_hours_per_day']) ? (float)$detail['contract_hours_per_day'] : 8.0;
  $fullTime = isset($detail['full_time']) ? (int)$detail['full_time'] : 1;
  $hireDate = isset($detail['hire_date']) ? $detail['hire_date'] : null;

  $stmt = $pdo->prepare('INSERT INTO user_detail (user_id, use_vehicle, contract_hours_per_day, full_time, hire_date, retire_date)
                           VALUES (?, ?, ?, ?, ?, ?)
                           ON DUPLICATE KEY UPDATE retire_date = VALUES(retire_date)');
  $stmt->execute([$targetId, $useV, $contractH, $fullTime, $hireDate, $retire_date]);

  $pdo->commit();
  echo json_encode(['success' => true]);
} catch (Exception $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  http_response_code(500);
  echo json_encode(['error' => 'db error']);
}
