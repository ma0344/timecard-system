<?php
/*
 * 目的: 監査ログの一覧を取得します。
 * 入力: 期間・操作種類などのフィルタ（任意）
 * 出力: 監査ログのリスト（誰がいつ何をしたか）
 */
?>
<?php
// admin-only: unified audit list (leave_request_audit + audit_logs)
session_start();
require_once '../db_config.php';

function is_admin($pdo, $uid) {
  $st = $pdo->prepare('SELECT role FROM users WHERE id = ?');
  $st->execute([$uid]);
  $r = $st->fetch(PDO::FETCH_ASSOC);
  return $r && $r['role'] === 'admin';
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method !== 'GET') {
  http_response_code(405);
  header('Content-Type: application/json');
  echo json_encode(['error' => 'method not allowed']);
  exit;
}
if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  header('Content-Type: application/json');
  echo json_encode(['error' => 'not logged in']);
  exit;
}
$uid = (int)$_SESSION['user_id'];
if (!is_admin($pdo, $uid)) {
  http_response_code(403);
  header('Content-Type: application/json');
  echo json_encode(['error' => 'forbidden']);
  exit;
}

// Ensure tables exist (best-effort)
try {
  $pdo->exec('CREATE TABLE IF NOT EXISTS leave_request_audit (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        request_id INT NOT NULL,
        action ENUM("create","open","approve","reject") NOT NULL,
        actor_type ENUM("user","admin","token","system") NOT NULL,
        actor_id INT NULL,
        ip VARCHAR(45) NULL,
        user_agent VARCHAR(255) NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX (request_id), INDEX (action), INDEX (actor_type)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
} catch (Throwable $e) {
}
try {
  $pdo->exec('CREATE TABLE IF NOT EXISTS audit_logs (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        actor_user_id INT NULL,
        target_user_id INT NULL,
        action VARCHAR(100) NOT NULL,
        details JSON NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX(actor_user_id), INDEX(target_user_id), INDEX(action), INDEX(created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
} catch (Throwable $e) {
}

// Params
$start = isset($_GET['start']) ? $_GET['start'] : null; // YYYY-MM-DD
$end = isset($_GET['end']) ? $_GET['end'] : null;       // YYYY-MM-DD
$type = isset($_GET['type']) ? $_GET['type'] : 'all';   // all|leave|paid
$action = isset($_GET['action']) ? trim((string)$_GET['action']) : '';
$limit = isset($_GET['limit']) ? max(1, min(500, (int)$_GET['limit'])) : 100;
$offset = isset($_GET['offset']) ? max(0, (int)$_GET['offset']) : 0;
$format = isset($_GET['format']) ? strtolower($_GET['format']) : 'json'; // json|csv

$whereLR = [];
$paramsLR = [];
if ($start) {
  $whereLR[] = 'created_at >= ?';
  $paramsLR[] = $start . ' 00:00:00';
}
if ($end) {
  $whereLR[] = 'created_at <= ?';
  $paramsLR[] = $end . ' 23:59:59';
}
if ($action) {
  $whereLR[] = 'action = ?';
  $paramsLR[] = $action;
}
$lrSql = 'SELECT created_at, "leave_request" AS source, action, actor_type, actor_id,
                 request_id AS ref_id, ip, user_agent, NULL AS details_json
          FROM leave_request_audit';
if ($whereLR) $lrSql .= ' WHERE ' . implode(' AND ', $whereLR);

$whereAL = [];
$paramsAL = [];
if ($start) {
  $whereAL[] = 'created_at >= ?';
  $paramsAL[] = $start . ' 00:00:00';
}
if ($end) {
  $whereAL[] = 'created_at <= ?';
  $paramsAL[] = $end . ' 23:59:59';
}
if ($action) {
  $whereAL[] = 'action = ?';
  $paramsAL[] = $action;
}
$alSql = 'SELECT created_at, "audit_logs" AS source, action,
                 "admin" AS actor_type, actor_user_id AS actor_id,
                 target_user_id AS ref_id, NULL AS ip, NULL AS user_agent,
                 details AS details_json
          FROM audit_logs';
if ($whereAL) $alSql .= ' WHERE ' . implode(' AND ', $whereAL);

$sqls = [];
$params = [];
if ($type === 'all' || $type === 'leave') {
  $sqls[] = $lrSql;
  $params = array_merge($params, $paramsLR);
}
if ($type === 'all' || $type === 'paid') {
  $sqls[] = $alSql;
  $params = array_merge($params, $paramsAL);
}

if (!$sqls) {
  header('Content-Type: application/json');
  echo json_encode(['ok' => true, 'items' => [], 'total' => 0]);
  exit;
}

$union = '(' . implode(') UNION ALL (', $sqls) . ') AS u';
$countSql = 'SELECT COUNT(*) FROM ' . $union;
$listSql = 'SELECT * FROM ' . $union . ' ORDER BY created_at DESC LIMIT ? OFFSET ?';

try {
  // count
  $st = $pdo->prepare($countSql);
  $st->execute($params);
  $total = (int)$st->fetchColumn();

  // list
  $st = $pdo->prepare($listSql);
  $execParams = array_merge($params, [$limit, $offset]);
  $st->execute($execParams);
  $rows = $st->fetchAll(PDO::FETCH_ASSOC);

  if ($format === 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="audit_export.csv"');
    // UTF-8 BOM
    echo "\xEF\xBB\xBF";
    $out = fopen('php://output', 'w');
    fputcsv($out, ['created_at', 'source', 'action', 'actor_type', 'actor_id', 'ref_id', 'ip', 'user_agent', 'details_json']);
    foreach ($rows as $r) {
      fputcsv($out, [
        $r['created_at'],
        $r['source'],
        $r['action'],
        $r['actor_type'],
        $r['actor_id'],
        $r['ref_id'],
        $r['ip'],
        $r['user_agent'],
        $r['details_json']
      ]);
    }
    fclose($out);
    exit;
  }

  header('Content-Type: application/json');
  echo json_encode(['ok' => true, 'total' => $total, 'items' => $rows]);
} catch (Throwable $e) {
  http_response_code(500);
  header('Content-Type: application/json');
  // 管理者専用API: 例外をサーバーログに記録
  error_log('api/audit_list.php error: ' . $e->getMessage());
  echo json_encode(['error' => 'db error']);
}
