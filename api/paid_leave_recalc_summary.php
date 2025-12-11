<?php
/*
 * 目的: 有給残高の再計算サマリを返します（確認用）。
 * 入力: なし（必要なら対象期間/ユーザーの指定）
 * 出力: 再計算結果の要約（残高、対象件数 等）
 */
?>
<?php
// api/paid_leave_recalc_summary.php
// 単一ユーザーのサマリとper-grant消化量(consumed_hours_total)を再計算する
session_start();
header('Content-Type: application/json');
require_once '../db_config.php';

if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(['error' => 'not logged in']);
  exit;
}

// 管理者のみ
$adminId = $_SESSION['user_id'];
$stmt = $pdo->prepare('SELECT role FROM users WHERE id = ?');
$stmt->execute([$adminId]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$admin || $admin['role'] !== 'admin') {
  http_response_code(403);
  echo json_encode(['error' => 'forbidden']);
  exit;
}

$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
if ($userId <= 0) {
  http_response_code(400);
  echo json_encode(['error' => 'user_id required']);
  exit;
}

try {
  $pdo->beginTransaction();
  // ログタイプ: EXPIRE のIDを取得（なければフォールバック3）
  $logTypeExpire = null;
  $lt = $pdo->query("SELECT id FROM log_types WHERE code = 'EXPIRE' LIMIT 1");
  if ($lt) {
    $logTypeExpire = $lt->fetchColumn();
  }
  if (!$logTypeExpire) {
    $logTypeExpire = 3;
  }
  // サマリ行を用意
  $pdo->prepare('INSERT IGNORE INTO user_leave_summary (user_id, balance_hours, used_total_hours, next_expire_date) VALUES (?, 0, 0, NULL)')->execute([$userId]);

  // per-grant消化をリセット
  $pdo->prepare('UPDATE paid_leaves SET consumed_hours_total = 0 WHERE user_id = ?')->execute([$userId]);

  // USEログをused_date昇順で再割当（FIFO）
  // 失効(EXPIRE)ログは再割当対象から除外する
  $logs = $pdo->prepare('SELECT id, used_date, used_hours FROM paid_leave_logs WHERE user_id = ? AND used_hours > 0 AND (log_type_id IS NULL OR log_type_id <> ?) ORDER BY used_date ASC, id ASC');
  $logs->execute([$userId, $logTypeExpire]);
  while ($log = $logs->fetch(PDO::FETCH_ASSOC)) {
    $needed = (float)$log['used_hours'];
    $usedDate = $log['used_date'];
    if ($needed <= 0) continue;
    $gr = $pdo->prepare('SELECT id, grant_hours, consumed_hours_total FROM paid_leaves WHERE user_id = ? AND (expire_date IS NULL OR expire_date > ?) AND grant_date <= ? AND (grant_hours - consumed_hours_total) > 0 ORDER BY grant_date ASC, id ASC');
    $gr->execute([$userId, $usedDate, $usedDate]);
    while ($needed > 1e-9 && ($g = $gr->fetch(PDO::FETCH_ASSOC))) {
      $rem = (float)$g['grant_hours'] - (float)$g['consumed_hours_total'];
      if ($rem <= 0) continue;
      $take = min($rem, $needed);
      $pdo->prepare('UPDATE paid_leaves SET consumed_hours_total = ROUND(consumed_hours_total + ?, 2) WHERE id = ?')
        ->execute([$take, (int)$g['id']]);
      $needed -= $take;
    }
    // 未充当分はそのまま。balanceへの反映は後で今日時点の残を集計する
  }

  // 今日時点の有効残= sum(active remaining)
  $stmt = $pdo->prepare('SELECT ROUND(IFNULL(SUM(GREATEST(grant_hours - consumed_hours_total, 0)),0),2) FROM paid_leaves WHERE user_id = ? AND (expire_date IS NULL OR expire_date > CURDATE())');
  $stmt->execute([$userId]);
  $balance = (float)$stmt->fetchColumn();

  // next_expire_date を再計算
  $stmt = $pdo->prepare('SELECT MIN(expire_date) FROM paid_leaves WHERE user_id = ? AND expire_date IS NOT NULL AND expire_date > CURDATE() AND (grant_hours - consumed_hours_total) > 0');
  $stmt->execute([$userId]);
  $next = $stmt->fetchColumn();

  // used_total_hours はログの総和
  // 合計利用は USE のみ（EXPIREは除外）
  $stmt = $pdo->prepare('SELECT ROUND(IFNULL(SUM(used_hours),0),2) FROM paid_leave_logs WHERE user_id = ? AND (log_type_id IS NULL OR log_type_id <> ?)');
  $stmt->execute([$userId, $logTypeExpire]);
  $usedTotal = (float)$stmt->fetchColumn();

  $upd = $pdo->prepare('UPDATE user_leave_summary SET balance_hours = ?, used_total_hours = ?, next_expire_date = ? WHERE user_id = ?');
  $upd->execute([$balance, $usedTotal, $next ?: null, $userId]);

  $pdo->commit();
  echo json_encode(['ok' => true, 'user_id' => $userId, 'balance_hours' => $balance, 'next_expire_date' => $next]);
} catch (Exception $e) {
  if ($pdo->inTransaction()) {
    $pdo->rollBack();
  }
  http_response_code(500);
  echo json_encode(['error' => 'db error']);
}
