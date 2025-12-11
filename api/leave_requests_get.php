<?php
/*
 * 目的: 自身の有給申請一覧を取得します。
 * 入力: 状態フィルタ（任意）、期間（任意）
 * 出力: 申請の詳細（申請日、状態、承認者など）
 */
?>
<?php
// api/leave_requests_get.php
// 管理者向け: 単一の有休申請詳細をIDで取得
session_start();
header('Content-Type: application/json');
require_once '../db_config.php';

if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(['error' => 'not logged in']);
  exit;
}
$adminId = (int)$_SESSION['user_id'];

$stmt = $pdo->prepare('SELECT role FROM users WHERE id = ?');
$stmt->execute([$adminId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row || $row['role'] !== 'admin') {
  http_response_code(403);
  echo json_encode(['error' => 'forbidden']);
  exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
  http_response_code(400);
  echo json_encode(['error' => 'invalid id']);
  exit;
}

try {
  $sql = 'SELECT lr.*, u.name FROM leave_requests lr JOIN users u ON u.id = lr.user_id WHERE lr.id = ? LIMIT 1';
  $stmt = $pdo->prepare($sql);
  $stmt->execute([$id]);
  $r = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$r) {
    http_response_code(404);
    echo json_encode(['error' => 'not found']);
    return;
  }
  echo json_encode([
    'ok' => true,
    'id' => (int)$r['id'],
    'user_id' => (int)$r['user_id'],
    'name' => (string)$r['name'],
    'used_date' => $r['used_date'],
    'hours' => isset($r['hours']) ? (float)$r['hours'] : null,
    'reason' => $r['reason'],
    'status' => $r['status'],
    'created_at' => $r['created_at'],
    'decided_at' => $r['decided_at'],
    'approver_user_id' => isset($r['approver_user_id']) ? (int)$r['approver_user_id'] : null
  ]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error' => 'db error']);
}
