<?php
/*
 * 目的: 有給の失効処理をバッチで行います。
 * 入力: 対象期間/ユーザー範囲
 * 出力: 失効件数・結果
 */
?>
<?php
// api/paid_leave_expire_batch.php
// 管理者が日次で叩くことを想定した失効処理（当日0:00で失効とみなす）
session_start();
header('Content-Type: application/json');
require_once '../db_config.php';

if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(['error' => 'not logged in']);
  exit;
}

// 管理者チェック
$adminId = $_SESSION['user_id'];
$stmt = $pdo->prepare('SELECT role FROM users WHERE id = ?');
$stmt->execute([$adminId]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$admin || $admin['role'] !== 'admin') {
  http_response_code(403);
  echo json_encode(['error' => 'forbidden']);
  exit;
}

$today = date('Y-m-d');
// オプション: 特定ユーザーのみ対象にする（例: ?user=2）
$targetUserId = isset($_GET['user']) ? (int)$_GET['user'] : null;
// オプション: 任意日付の失効処理（例: ?date=2025-10-01）。未指定なら本日。
$runDate = isset($_GET['date']) ? $_GET['date'] : $today;
// オプション: 過去分の失効を一括で洗い直すモード（例: ?mode=backfill）。date指定は無視され、expire_date<=本日が対象。
$mode = isset($_GET['mode']) ? $_GET['mode'] : null;
if ($mode !== 'backfill' && $runDate !== $today) {
  // 簡易バリデーション: YYYY-MM-DD 形式、かつ未来日は不可
  if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $runDate)) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid date format']);
    exit;
  }
  if ($runDate > $today) {
    http_response_code(400);
    echo json_encode(['error' => 'date must be today or past']);
    exit;
  }
}

try {
  // 同日二重実行ガード
  $pdo->beginTransaction();
  // ガード: 全体実行かつ通常モード（date指定実行）の場合のみ日次二重実行ガード。
  if ($mode !== 'backfill' && $targetUserId === null) {
    $chk = $pdo->prepare('SELECT 1 FROM leave_expire_runs WHERE run_date = ? FOR UPDATE');
    $chk->execute([$runDate]);
    if ($chk->fetch()) {
      $pdo->commit();
      echo json_encode(['ok' => true, 'skipped' => true, 'message' => 'already processed for this date', 'run_date' => $runDate]);
      exit;
    }
  }

  // ログタイプID（EXPIRE）
  $logTypeExpire = null;
  $lt = $pdo->query("SELECT id FROM log_types WHERE code = 'EXPIRE' LIMIT 1");
  if ($lt) {
    $logTypeExpire = $lt->fetchColumn();
  }
  if (!$logTypeExpire) {
    $logTypeExpire = 3;
  } // フォールバック

  // 取得クエリ: 通常モードは expire_date = runDate、バックフィルは expire_date <= today
  if ($mode === 'backfill') {
    $sql = "SELECT id, user_id, grant_hours, consumed_hours_total, expire_date
                FROM paid_leaves
                WHERE expire_date <= ? AND (grant_hours - consumed_hours_total) > 0";
    $params = [$today];
    if ($targetUserId !== null) {
      $sql .= " AND user_id = ?";
      $params[] = $targetUserId;
    }
  } else {
    // 指定日（既定: 本日）失効の各付与を取得（残があるもの）
    $sql = "SELECT id, user_id, grant_hours, consumed_hours_total, expire_date
                FROM paid_leaves
                WHERE expire_date = ? AND (grant_hours - consumed_hours_total) > 0";
    // ユーザー指定がある場合はそのユーザーに限定。
    $params = [$runDate];
    if ($targetUserId !== null) {
      $sql .= " AND user_id = ?";
      $params[] = $targetUserId;
    }
  }
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $grants = $stmt->fetchAll(PDO::FETCH_ASSOC);

  $deductByUser = [];
  foreach ($grants as $g) {
    $uid = (int)$g['user_id'];
    $rem = max(0.0, (float)$g['grant_hours'] - (float)$g['consumed_hours_total']);
    if ($rem <= 0) continue;
    // 重複EXPIREログ抑止（同一付与に対して当日EXPIREが既に記録されていればスキップ）
    $expireLogDate = ($mode === 'backfill') ? $g['expire_date'] : $runDate;
    $dupChk = $pdo->prepare('SELECT id, used_hours FROM paid_leave_logs WHERE paid_leave_id = ? AND used_date = ? AND log_type_id = ? LIMIT 1');
    $dupChk->execute([(int)$g['id'], $expireLogDate, $logTypeExpire]);
    $existing = $dupChk->fetch(PDO::FETCH_ASSOC);
    if ($existing) {
      // 重複あり: バックフィル、またはユーザー限定実行では補正（履歴の整合性を優先）
      if ($mode === 'backfill' || $targetUserId !== null) {
        $eid = (int)$existing['id'];
        $prev = (float)$existing['used_hours'];
        if (abs($prev - $rem) > 1e-9) {
          $upd = $pdo->prepare('UPDATE paid_leave_logs SET used_hours = ? WHERE id = ?');
          $upd->execute([$rem, $eid]);
        }
      }
      // 重複ログが既に存在する場合は新規挿入しない
    } else {
      // 失効ログ（paid_leave_id を紐付け、used_date= expireLogDate、理由=失効）
      $ins = $pdo->prepare('INSERT INTO paid_leave_logs (user_id, paid_leave_id, used_date, used_hours, reason, log_type_id) VALUES (?, ?, ?, ?, ?, ?)');
      $ins->execute([$uid, (int)$g['id'], $expireLogDate, $rem, '失効', $logTypeExpire]);
    }
    // サマリ控除のため集計
    $deductByUser[$uid] = ($deductByUser[$uid] ?? 0) + $rem;
  }

  // サマリ更新と next_expire_date 再計算
  // バックフィルモードではサマリは変更しない（recalcと併用時に二重控除となるため）
  if ($mode !== 'backfill') {
    foreach ($deductByUser as $uid => $amt) {
      // サマリ行を確実に作成
      $pdo->prepare('INSERT IGNORE INTO user_leave_summary (user_id, balance_hours, used_total_hours, next_expire_date) VALUES (?, 0, 0, NULL)')->execute([(int)$uid]);
      // 残高から控除
      $pdo->prepare('UPDATE user_leave_summary SET balance_hours = ROUND(balance_hours - ?, 2) WHERE user_id = ?')->execute([$amt, (int)$uid]);
      // next_expire_date 更新
      $minStmt = $pdo->prepare('SELECT MIN(expire_date) FROM paid_leaves WHERE user_id = ? AND expire_date > CURDATE() AND (grant_hours - consumed_hours_total) > 0');
      $minStmt->execute([(int)$uid]);
      $next = $minStmt->fetchColumn();
      $pdo->prepare('UPDATE user_leave_summary SET next_expire_date = ? WHERE user_id = ?')->execute([$next ?: null, (int)$uid]);
    }
  }

  // 実行印（全体実行時のみ）。バックフィルは印を付けない（複数日対象のため）。
  if ($mode !== 'backfill' && $targetUserId === null) {
    $pdo->prepare('INSERT INTO leave_expire_runs (run_date) VALUES (?)')->execute([$runDate]);
  }

  $pdo->commit();
  echo json_encode([
    'ok' => true,
    'processed_users' => count($deductByUser),
    'scope' => ($targetUserId === null ? 'all' : 'user'),
    'user_id' => ($targetUserId === null ? null : $targetUserId),
    'run_date' => ($mode === 'backfill' ? null : $runDate),
    'mode' => ($mode ?: 'normal')
  ]);
} catch (Exception $e) {
  if ($pdo->inTransaction()) {
    $pdo->rollBack();
  }
  http_response_code(500);
  echo json_encode(['error' => 'db error']);
}
