<?php
/*
 * 目的: 有給使用イベントを削除します（誤登録の取り消し等）。
 * 入力: event_id
 * 出力: 削除結果（成功/失敗）
 */
?>
<?php
// api/paid_leave_use_event_delete.php
// USEイベント（複数ログに跨る）の削除。EXPIREは対象外。
session_start();
header('Content-Type: application/json');
require_once '../db_config.php';

if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(['error' => 'not logged in']);
  exit;
}

// 管理者のみ
$actorId = $_SESSION['user_id'];
$stmt = $pdo->prepare('SELECT role FROM users WHERE id = ?');
$stmt->execute([$actorId]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$admin || $admin['role'] !== 'admin') {
  http_response_code(403);
  echo json_encode(['error' => 'forbidden']);
  exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$eventId = isset($data['event_id']) ? (int)$data['event_id'] : 0;
$userId = isset($data['user_id']) ? (int)$data['user_id'] : 0;
if ($eventId <= 0 || $userId <= 0) {
  http_response_code(400);
  echo json_encode(['error' => 'event_id and user_id required']);
  exit;
}

try {
  $pdo->beginTransaction();
  // 失効ID
  $logTypeExpire = (int)($pdo->query("SELECT id FROM log_types WHERE code = 'EXPIRE' LIMIT 1")->fetchColumn() ?: 3);

  // 対象ログ取得（USEのみ）
  $rows = $pdo->prepare('SELECT id, paid_leave_id, used_hours FROM paid_leave_logs WHERE user_id = ? AND event_id = ? AND (log_type_id IS NULL OR log_type_id <> ?)');
  $rows->execute([$userId, $eventId, $logTypeExpire]);
  $logs = $rows->fetchAll(PDO::FETCH_ASSOC);
  $delCount = count($logs);
  if ($delCount === 0) {
    // 何もなければOK扱い
    $pdo->commit();
    echo json_encode(['ok' => true, 'deleted' => 0]);
    return;
  }
  // per-grant消費を戻す
  foreach ($logs as $l) {
    if ($l['paid_leave_id']) {
      $pdo->prepare('UPDATE paid_leaves SET consumed_hours_total = ROUND(GREATEST(consumed_hours_total - ?, 0), 2) WHERE id = ?')
        ->execute([(float)$l['used_hours'], (int)$l['paid_leave_id']]);
    }
  }
  // ログ削除
  $pdo->prepare('DELETE FROM paid_leave_logs WHERE user_id = ? AND event_id = ?')->execute([$userId, $eventId]);
  // イベント本体も削除（監査は audit_logs 側に残す）
  $pdo->prepare('DELETE FROM paid_leave_use_events WHERE id = ? AND user_id = ?')->execute([$eventId, $userId]);
  // 監査
  try {
    $pdo->prepare('INSERT INTO audit_logs (actor_user_id, target_user_id, action, details) VALUES (?, ?, ?, JSON_OBJECT("event_id", ?))')
      ->execute([$actorId, $userId, 'paid_leave_use_event_delete', $eventId]);
  } catch (Exception $e) {
  }

  // サマリ再計算（このユーザーのみ）
  // replicate core of recalc
  // reset consumed
  $pdo->prepare('UPDATE paid_leaves SET consumed_hours_total = 0 WHERE user_id = ?')->execute([$userId]);
  // exclude EXPIRE
  $stmtLogs = $pdo->prepare('SELECT used_date, used_hours FROM paid_leave_logs WHERE user_id = ? AND used_hours > 0 AND (log_type_id IS NULL OR log_type_id <> ?) ORDER BY used_date ASC, id ASC');
  $stmtLogs->execute([$userId, $logTypeExpire]);
  while ($log = $stmtLogs->fetch(PDO::FETCH_ASSOC)) {
    $needed = (float)$log['used_hours'];
    $usedDate = $log['used_date'];
    $gr = $pdo->prepare('SELECT id, grant_hours, consumed_hours_total FROM paid_leaves WHERE user_id = ? AND (expire_date IS NULL OR expire_date > ?) AND grant_date <= ? AND (grant_hours - consumed_hours_total) > 0 ORDER BY grant_date ASC, id ASC');
    $gr->execute([$userId, $usedDate, $usedDate]);
    while ($needed > 1e-9 && ($g = $gr->fetch(PDO::FETCH_ASSOC))) {
      $rem = (float)$g['grant_hours'] - (float)$g['consumed_hours_total'];
      if ($rem <= 0) continue;
      $take = min($rem, $needed);
      $pdo->prepare('UPDATE paid_leaves SET consumed_hours_total = ROUND(consumed_hours_total + ?, 2) WHERE id = ?')->execute([$take, (int)$g['id']]);
      $needed -= $take;
    }
  }
  // balance/next/used_total
  $stmt = $pdo->prepare('SELECT ROUND(IFNULL(SUM(GREATEST(grant_hours - consumed_hours_total, 0)),0),2) FROM paid_leaves WHERE user_id = ? AND (expire_date IS NULL OR expire_date > CURDATE())');
  $stmt->execute([$userId]);
  $balance = (float)$stmt->fetchColumn();
  $stmt = $pdo->prepare('SELECT MIN(expire_date) FROM paid_leaves WHERE user_id = ? AND expire_date IS NOT NULL AND expire_date > CURDATE() AND (grant_hours - consumed_hours_total) > 0');
  $stmt->execute([$userId]);
  $next = $stmt->fetchColumn();
  $stmt = $pdo->prepare('SELECT ROUND(IFNULL(SUM(used_hours),0),2) FROM paid_leave_logs WHERE user_id = ? AND (log_type_id IS NULL OR log_type_id <> ?)');
  $stmt->execute([$userId, $logTypeExpire]);
  $usedTotal = (float)$stmt->fetchColumn();
  $pdo->prepare('INSERT IGNORE INTO user_leave_summary (user_id, balance_hours, used_total_hours, next_expire_date) VALUES (?, 0, 0, NULL)')->execute([$userId]);
  $pdo->prepare('UPDATE user_leave_summary SET balance_hours = ?, used_total_hours = ?, next_expire_date = ? WHERE user_id = ?')->execute([$balance, $usedTotal, $next ?: null, $userId]);

  $pdo->commit();
  echo json_encode(['ok' => true, 'deleted' => $delCount]);
} catch (Exception $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  http_response_code(500);
  echo json_encode(['error' => 'db error']);
}
