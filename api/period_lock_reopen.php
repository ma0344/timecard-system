<?php
/*
 * 目的: 期間ロックを再開放します（編集再許可）。
 * 入力: ロックID、理由
 * 出力: 成功/失敗（更新結果）
 */
?>
<?php
/*
 * 目的: ロック期間を再開（解除）します。
 * 入力: lock_id など
 * 出力: 実行結果
 */
?>
<?php
// api/period_lock_reopen.php
// Admin: reopen a previously locked period (change status to reopened) by id
// POST JSON: { id:number, note? }

session_start();
header('Content-Type: application/json');
require_once '../db_config.php';

if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(['error' => 'not logged in']);
  exit;
}
$actor = intval($_SESSION['user_id']);
$roleStmt = $pdo->prepare('SELECT role FROM users WHERE id=?');
$roleStmt->execute([$actor]);
$me = $roleStmt->fetch(PDO::FETCH_ASSOC);
if (!$me || $me['role'] !== 'admin') {
  http_response_code(403);
  echo json_encode(['error' => 'forbidden']);
  exit;
}

$in = json_decode(file_get_contents('php://input'), true) ?: [];
$id = isset($in['id']) ? intval($in['id']) : 0;
$note = isset($in['note']) ? trim(strval($in['note'])) : null;
if ($id <= 0) {
  http_response_code(400);
  echo json_encode(['error' => 'invalid id']);
  exit;
}

try {
  // Ensure exists and not already reopened
  $check = $pdo->prepare('SELECT id,status FROM attendance_period_locks WHERE id=?');
  $check->execute([$id]);
  $row = $check->fetch(PDO::FETCH_ASSOC);
  if (!$row) {
    http_response_code(404);
    echo json_encode(['error' => 'not found']);
    exit;
  }
  if ($row['status'] === 'reopened') {
    echo json_encode(['ok' => true, 'already' => true]);
    exit;
  }

  $upd = $pdo->prepare('UPDATE attendance_period_locks SET status="reopened", reopened_at=NOW(), reopened_by=?, note=COALESCE(?,note) WHERE id=?');
  $upd->execute([$actor, $note, $id]);
  echo json_encode(['ok' => true]);
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(['error' => 'server error']);
}
