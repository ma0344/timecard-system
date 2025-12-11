<?php
/*
 * 目的: 指定期間の有効な日ステータス（work/off/paid/ignore）を返します。
 * 入力: start, end（日付範囲）
 * 出力: 各日のステータス一覧（ビュー day_status_effective 前提）
 */
?>
<?php
// api/day_status_effective_get.php
// 自分自身の期間内の有効な日ステータスを返す（ログイン必須／一般ユーザー可）
session_start();
header('Content-Type: application/json');
require_once '../db_config.php';

if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(['error' => 'not logged in']);
  exit;
}
$userId = intval($_SESSION['user_id']);

$start = isset($_GET['start']) ? $_GET['start'] : date('Y-m-d');
$end   = isset($_GET['end']) ? $_GET['end'] : $start;

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end)) {
  http_response_code(400);
  echo json_encode(['error' => 'invalid date']);
  exit;
}

function map_status($raw) {
  switch ($raw) {
    case 'off_full':
      return 'off';
    case 'off_am':
      return 'am_off';
    case 'off_pm':
      return 'pm_off';
    case 'ignore':
      return 'ignore';
    case 'working':
      return 'work';
    default:
      return $raw;
  }
}

try {
  // まずはビュー(day_status_effective)から取得を試みる
  $sql = "SELECT date, status, source, note FROM day_status_effective WHERE user_id = ? AND date BETWEEN ? AND ? ORDER BY date";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([$userId, $start, $end]);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  // 正常に取れた場合はそのまま返す
  echo json_encode(['ok' => true, 'items' => $rows]);
} catch (Exception $e) {
  // ビュー未作成などのケースではオーバーライドのみで近似（既定はフロント側でwork扱い）
  try {
    $sql2 = "SELECT date, status, note, created_at FROM day_status_overrides WHERE user_id = ? AND date BETWEEN ? AND ? AND revoked_at IS NULL ORDER BY date, created_at DESC";
    $stmt2 = $pdo->prepare($sql2);
    $stmt2->execute([$userId, $start, $end]);
    $tmp = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    $picked = [];
    foreach ($tmp as $r) {
      $d = $r['date'];
      if (!isset($picked[$d])) {
        $picked[$d] = [
          'date' => $d,
          'status' => map_status($r['status']),
          'source' => 'override',
          'note' => $r['note'] ?? null,
        ];
      }
    }
    echo json_encode(['ok' => true, 'items' => array_values($picked)]);
  } catch (Exception $e2) {
    http_response_code(500);
    echo json_encode(['error' => 'server error']);
  }
}
