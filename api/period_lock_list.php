<?php
/*
 * 目的: 期間ロックの一覧を取得します。
 * 入力: フィルタ条件（期間、状態）
 * 出力: ロック一覧（開始/終了、状態、作成者 等）
 */
?>
<?php
/*
 * 目的: 期間ロックの一覧を取得します（締め運用）。
 * 入力: 期間等のフィルタ（任意）
 * 出力: ロック期間の一覧
 */
?>
<?php
// api/period_lock_list.php
// Admin: list lock records. Optional filters: user_id, start, end. Returns all rows (history included).

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

$userId = isset($_GET['user_id']) ? (strlen($_GET['user_id']) ? intval($_GET['user_id']) : null) : null; // '' or missing => null
$start  = isset($_GET['start']) ? $_GET['start'] : null;
$end    = isset($_GET['end']) ? $_GET['end'] : null;

$params = [];
$where = [];
if (!is_null($userId)) {
  $where[] = 'user_id = ?';
  $params[] = $userId;
}
if ($start && preg_match('/^\d{4}-\d{2}-\d{2}$/', $start)) {
  $where[] = 'end_date >= ?';
  $params[] = $start;
}
if ($end && preg_match('/^\d{4}-\d{2}-\d{2}$/', $end)) {
  $where[] = 'start_date <= ?';
  $params[] = $end;
}
$sql = 'SELECT id,user_id,start_date,end_date,status,locked_at,locked_by,reopened_at,reopened_by,note,version FROM attendance_period_locks';
if ($where) {
  $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY COALESCE(user_id,0), start_date';

try {
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  echo json_encode(['ok' => true, 'items' => $rows]);
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(['error' => 'server error']);
}
