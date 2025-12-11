<?php
/*
 * 目的: 指定期間の有給取得履歴を返します。
 * 入力: start, end（日付範囲）
 * 出力: 使用イベント（取得日・時間数・理由等）の一覧
 */
?>
<?php
// api/paid_leave_history.php
session_start();
header('Content-Type: application/json');
require_once '../db_config.php';

if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(['error' => 'not logged in']);
  exit;
}

// ロール確認
$loginId = $_SESSION['user_id'];
$stmt = $pdo->prepare('SELECT role FROM users WHERE id = ?');
$stmt->execute([$loginId]);
$me = $stmt->fetch(PDO::FETCH_ASSOC);
$isAdmin = $me && $me['role'] === 'admin';

// 範囲取得モード（start/endが指定された場合は一般ユーザーも自分分の取得を許可）
$start = isset($_GET['start']) ? $_GET['start'] : null;
$end = isset($_GET['end']) ? $_GET['end'] : null;
$reqUserId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;

if ($start && $end) {
  $uid = $isAdmin && $reqUserId ? $reqUserId : $loginId;
  $items = [];
  try {
    $stmtEv = $pdo->prepare('SELECT id AS event_id, used_date, total_hours AS used_hours, reason FROM paid_leave_use_events WHERE user_id = ? AND used_date BETWEEN ? AND ? ORDER BY used_date');
    $stmtEv->execute([$uid, $start, $end]);
    $items = $stmtEv->fetchAll(PDO::FETCH_ASSOC) ?: [];
  } catch (Exception $e) {
    // テーブル未存在などの場合は空
    $items = [];
  }
  echo json_encode(['ok' => true, 'user_id' => $uid, 'items' => array_map(function ($r) {
    return [
      'date' => $r['used_date'],
      'hours' => (float)$r['used_hours'],
      'reason' => isset($r['reason']) ? $r['reason'] : null,
      'source' => 'event'
    ];
  }, $items)]);
  exit;
}

// 旧モード: 管理者のみ、履歴リスト
if (!$isAdmin) {
  http_response_code(403);
  echo json_encode(['error' => 'forbidden']);
  exit;
}

$targetId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
$limit = isset($_GET['limit']) ? max(1, min(200, (int)$_GET['limit'])) : 50;
if (!$targetId) {
  http_response_code(400);
  echo json_encode(['error' => 'user_id required']);
  exit;
}

$stmt = $pdo->prepare('SELECT id, grant_date, grant_hours, expire_date FROM paid_leaves WHERE user_id = ? ORDER BY grant_date DESC, id DESC LIMIT ?');
$stmt->bindValue(1, $targetId, PDO::PARAM_INT);
$stmt->bindValue(2, $limit, PDO::PARAM_INT);
$stmt->execute();
$grants = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ログベースの取得（後方互換用）
$stmt = $pdo->prepare('SELECT id, paid_leave_id, event_id, used_date, used_hours, reason, log_type_id FROM paid_leave_logs WHERE user_id = ? ORDER BY used_date DESC, id DESC LIMIT ?');
$stmt->bindValue(1, $targetId, PDO::PARAM_INT);
$stmt->bindValue(2, $limit, PDO::PARAM_INT);
$stmt->execute();
$uses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// イベントベースの取得（優先してUI表示に用いる）
$useEvents = [];
try {
  $stmtEv = $pdo->prepare('SELECT id AS event_id, used_date, total_hours AS used_hours, reason, created_at FROM paid_leave_use_events WHERE user_id = ? ORDER BY used_date DESC, id DESC LIMIT ?');
  $stmtEv->bindValue(1, $targetId, PDO::PARAM_INT);
  $stmtEv->bindValue(2, $limit, PDO::PARAM_INT);
  $stmtEv->execute();
  $useEvents = $stmtEv->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Exception $e) {
  // テーブル未存在などの場合は無視して後方互換の uses を利用
}

// 分類: USE と EXPIRE を分けたい場合はここで分割
$expires = [];
$usesOnly = [];
try {
  // ログタイプのIDを判別（任意）
  $map = [];
  $q = $pdo->query("SELECT id, code FROM log_types WHERE code IN ('USE','EXPIRE')");
  if ($q) {
    foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $r) {
      $map[$r['id']] = $r['code'];
    }
  }
  foreach ($uses as $row) {
    $code = isset($map[$row['log_type_id']]) ? $map[$row['log_type_id']] : null;
    if ($code === 'EXPIRE') $expires[] = $row;
    else $usesOnly[] = $row;
  }
} catch (Exception $e) {
  // 失敗時は全て uses に
  $usesOnly = $uses;
}

echo json_encode([
  'user_id' => $targetId,
  'grants' => $grants,
  'uses' => $usesOnly,       // 後方互換: ログ単位
  'use_events' => $useEvents, // 推奨: イベント単位
  'expires' => $expires,
]);
