<?php
/*
 * 目的: 管理者による有給申請の決裁（承認/却下）を行います。
 * 入力: request_id、decision、管理者コメント等
 * 出力: 処理結果（成功/失敗）と通知生成
 */
?>
<?php
// api/leave_requests_decide_admin.php
// 管理者の承認/却下（IDベース、セッション必須）
session_start();
header('Content-Type: application/json');
require_once '../db_config.php';

// メソッド強制
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
  http_response_code(405);
  echo json_encode(['error' => 'method not allowed']);
  exit;
}

// Referer がある場合は同一ホストを要求
$ref = isset($_SERVER['HTTP_REFERER']) ? trim((string)$_SERVER['HTTP_REFERER']) : '';
if ($ref !== '') {
  $refHost = parse_url($ref, PHP_URL_HOST);
  $srvHost = $_SERVER['HTTP_HOST'] ?? '';
  if ($refHost && $srvHost && !preg_match('/^' . preg_quote($refHost, '/') . '(?:\:\d+)?$/', $srvHost)) {
    http_response_code(403);
    echo json_encode(['error' => 'forbidden']);
    exit;
  }
}

// 設定からレート制限の値を取得
function rate_cfg($pdo, $endpoint, $defL, $defW) {
  try {
    $st = $pdo->prepare('SELECT value FROM app_settings WHERE `key`=? LIMIT 1');
    $st->execute(['rate_limit']);
    $val = $st->fetchColumn();
    $j = $val ? json_decode($val, true) : null;
    if (is_array($j) && isset($j[$endpoint])) {
      $l = isset($j[$endpoint]['limit']) ? (int)$j[$endpoint]['limit'] : $defL;
      $w = isset($j[$endpoint]['window']) ? (int)$j[$endpoint]['window'] : $defW;
      if ($l > 0 && $w > 0) return [$l, $w];
    }
  } catch (Throwable $e) {
  }
  return [$defL, $defW];
}

// レート制限（IP単位・管理操作向けに緩め）
function rate_limit($pdo, $endpoint, $limit, $windowSec) {
  $pdo->exec('CREATE TABLE IF NOT EXISTS request_rate_limit (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        ip VARCHAR(45) NOT NULL,
        endpoint VARCHAR(100) NOT NULL,
        period_start DATETIME NOT NULL,
        count INT NOT NULL DEFAULT 0,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_ip_ep (ip, endpoint)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
  $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
  $upd = $pdo->prepare('UPDATE request_rate_limit
        SET count = IF(TIMESTAMPDIFF(SECOND, period_start, NOW()) < ?, count+1, 1),
            period_start = IF(TIMESTAMPDIFF(SECOND, period_start, NOW()) < ?, period_start, NOW())
        WHERE ip = ? AND endpoint = ?');
  $upd->execute([$windowSec, $windowSec, $ip, $endpoint]);
  if ($upd->rowCount() === 0) {
    $ins = $pdo->prepare('INSERT INTO request_rate_limit (ip, endpoint, period_start, count) VALUES (?,?,NOW(),1)');
    try {
      $ins->execute([$ip, $endpoint]);
    } catch (Throwable $e) {
    }
  }
  $sel = $pdo->prepare('SELECT count, period_start FROM request_rate_limit WHERE ip = ? AND endpoint = ?');
  $sel->execute([$ip, $endpoint]);
  $r = $sel->fetch(PDO::FETCH_ASSOC);
  if (!$r) return true;
  if ((int)$r['count'] > $limit && (time() - strtotime($r['period_start'])) < $windowSec) return false;
  return true;
}

list($lim, $win) = rate_cfg($pdo, 'leave_requests_decide_admin', 30, 300);
if (!rate_limit($pdo, 'leave_requests_decide_admin', $lim, $win)) {
  usleep(random_int(100000, 300000));
  http_response_code(429);
  echo json_encode(['error' => 'too many requests']);
  exit;
}

if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(['error' => 'not logged in']);
  exit;
}
$adminId = (int)$_SESSION['user_id'];

// 管理者チェック
$stmt = $pdo->prepare('SELECT role FROM users WHERE id = ?');
$stmt->execute([$adminId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row || $row['role'] !== 'admin') {
  http_response_code(403);
  echo json_encode(['error' => 'forbidden']);
  exit;
}

$data = $_POST;
if (empty($data)) {
  $raw = json_decode(file_get_contents('php://input'), true);
  if (is_array($raw)) $data = $raw;
}

$id = isset($data['id']) ? (int)$data['id'] : 0;
$action = isset($data['action']) ? trim((string)$data['action']) : '';
if ($id <= 0 || !in_array($action, ['approve', 'reject'], true)) {
  http_response_code(400);
  echo json_encode(['error' => 'invalid params']);
  exit;
}

try {
  // テーブルの最低限のスキーマ（初期化用）
  $pdo->exec('CREATE TABLE IF NOT EXISTS leave_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        used_date DATE NOT NULL,
        hours DECIMAL(6,2) NOT NULL,
        reason TEXT NULL,
        status ENUM("pending","approved","rejected") NOT NULL DEFAULT "pending",
        approver_user_id INT NULL,
        decided_at DATETIME NULL,
        decided_ip VARCHAR(45) NULL,
        decided_user_agent VARCHAR(255) NULL,
        approve_token VARCHAR(128) UNIQUE,
        approve_token_hash CHAR(64) NULL,
        approve_token_expires_at DATETIME NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX (status), INDEX (approve_token), INDEX (approve_token_hash)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

  $stmt = $pdo->prepare('SELECT id, user_id, used_date, hours, status FROM leave_requests WHERE id = ? LIMIT 1');
  $stmt->execute([$id]);
  $req = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$req) {
    http_response_code(404);
    echo json_encode(['error' => 'not found']);
    exit;
  }
  if ($req['status'] !== 'pending') {
    echo json_encode(['warning' => 'already decided', 'status' => $req['status']]);
    exit;
  }

  $newStatus = $action === 'approve' ? 'approved' : 'rejected';
  $ip = $_SERVER['REMOTE_ADDR'] ?? null;
  $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
  $upd = $pdo->prepare('UPDATE leave_requests SET status = ?, approver_user_id = ?, decided_at = NOW(), decided_ip = ?, decided_user_agent = ?, approve_token = NULL, approve_token_expires_at = NULL WHERE id = ?');
  $upd->execute([$newStatus, $adminId, $ip, $ua, $id]);

  // 監査テーブル
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
  $al = $pdo->prepare('INSERT INTO leave_request_audit (request_id, action, actor_type, actor_id, ip, user_agent) VALUES (?,?,?,?,?,?)');
  $al->execute([$id, $action, 'admin', $adminId, $ip, $ua]);

  // SSE通知: 承認/却下の結果を本人へプッシュ
  try {
    require_once __DIR__ . '/lib/events.php';
    $payload = [
      'request_id' => (int)$req['id'],
      'status' => $newStatus,
      'used_date' => $req['used_date'],
      'hours' => (float)$req['hours']
    ];
    events_enqueue((int)$req['user_id'], 'leave_request_decided', $payload);
    if ($newStatus === 'approved') {
      events_enqueue((int)$req['user_id'], 'paid_leave_updated', $payload);
    }
  } catch (Throwable $e) {
  }

  // 承認時: 有給使用イベントを即時確定し、履歴に反映
  if ($newStatus === 'approved') {
    $userId = (int)$req['user_id'];
    $usedDate = $req['used_date'];
    $usedHours = (float)$req['hours'];
    $logTypeUse = (int)($pdo->query("SELECT id FROM log_types WHERE code = 'USE' LIMIT 1")->fetchColumn() ?: 2);
    $logTypeExpire = (int)($pdo->query("SELECT id FROM log_types WHERE code = 'EXPIRE' LIMIT 1")->fetchColumn() ?: 3);
    $st = $pdo->prepare('SELECT COALESCE(negative_balance_allowed, 0) AS allow_negative FROM user_leave_settings WHERE user_id = ?');
    $st->execute([$userId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    $allowNegative = $row ? ((int)$row['allow_negative'] === 1) : false;

    $pdo->prepare('CREATE TABLE IF NOT EXISTS paid_leave_use_events (id BIGINT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, used_date DATE NOT NULL, total_hours DECIMAL(6,2) NOT NULL, reason TEXT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX(user_id), INDEX(used_date)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4')->execute();
    $pdo->prepare('INSERT INTO paid_leave_use_events (user_id, used_date, total_hours, reason) VALUES (?, ?, ?, ?)')
      ->execute([$userId, $usedDate, $usedHours, '管理者承認により自動反映']);
    $eventId = (int)$pdo->lastInsertId();

    $needed = $usedHours;
    $alloc = $pdo->prepare('SELECT id, grant_date, grant_hours, consumed_hours_total, expire_date FROM paid_leaves WHERE user_id = ? AND (expire_date IS NULL OR expire_date > ?) AND grant_date <= ? AND (grant_hours - consumed_hours_total) > 0 ORDER BY grant_date ASC, id ASC');
    $alloc->execute([$userId, $usedDate, $usedDate]);
    while ($needed > 1e-9 && ($g = $alloc->fetch(PDO::FETCH_ASSOC))) {
      $rem = (float)$g['grant_hours'] - (float)$g['consumed_hours_total'];
      if ($rem <= 0) continue;
      $take = min($rem, $needed);
      $pdo->prepare('INSERT INTO paid_leave_logs (user_id, paid_leave_id, event_id, used_date, used_hours, reason, log_type_id) VALUES (?, ?, ?, ?, ?, ?, ?)')
        ->execute([$userId, (int)$g['id'], $eventId, $usedDate, $take, '承認', $logTypeUse]);
      $pdo->prepare('UPDATE paid_leaves SET consumed_hours_total = ROUND(consumed_hours_total + ?, 2) WHERE id = ?')
        ->execute([$take, (int)$g['id']]);
      $needed -= $take;
    }
    if ($needed > 1e-9 && !$allowNegative) {
      // 不足分は記録のみ
    }
    if ($needed > 1e-9) {
      $pdo->prepare('INSERT INTO paid_leave_logs (user_id, paid_leave_id, event_id, used_date, used_hours, reason, log_type_id) VALUES (?, NULL, ?, ?, ?, ?, ?)')
        ->execute([$userId, $eventId, $usedDate, $needed, '承認（未充当分）', $logTypeUse]);
    }

    // サマリ再計算
    try {
      $pdo->prepare('UPDATE paid_leaves SET consumed_hours_total = 0 WHERE user_id = ?')->execute([$userId]);
      $logs = $pdo->prepare('SELECT used_date, used_hours FROM paid_leave_logs WHERE user_id = ? AND used_hours > 0 AND (log_type_id IS NULL OR log_type_id <> ?) ORDER BY used_date ASC, id ASC');
      $logs->execute([$userId, $logTypeExpire]);
      while ($log = $logs->fetch(PDO::FETCH_ASSOC)) {
        $need = (float)$log['used_hours'];
        $u = $log['used_date'];
        $gr = $pdo->prepare('SELECT id, grant_hours, consumed_hours_total FROM paid_leaves WHERE user_id = ? AND (expire_date IS NULL OR expire_date > ?) AND grant_date <= ? AND (grant_hours - consumed_hours_total) > 0 ORDER BY grant_date ASC, id ASC');
        $gr->execute([$userId, $u, $u]);
        while ($need > 1e-9 && ($gg = $gr->fetch(PDO::FETCH_ASSOC))) {
          $rem = (float)$gg['grant_hours'] - (float)$gg['consumed_hours_total'];
          if ($rem <= 0) continue;
          $tk = min($rem, $need);
          $pdo->prepare('UPDATE paid_leaves SET consumed_hours_total = ROUND(consumed_hours_total + ?, 2) WHERE id = ?')->execute([$tk, (int)$gg['id']]);
          $need -= $tk;
        }
      }
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
    } catch (Throwable $e) {
    }
  }

  // 通知（申請者）
  try {
    $pdo->exec('CREATE TABLE IF NOT EXISTS notifications (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            type VARCHAR(50) NOT NULL,
            title VARCHAR(200) NOT NULL,
            body TEXT NULL,
            link VARCHAR(255) NULL,
            status ENUM("unread","read") NOT NULL DEFAULT "unread",
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            read_at DATETIME NULL,
            INDEX(user_id), INDEX(status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    $title = ($newStatus === 'approved') ? '有給申請が承認されました' : '有給申請が却下されました';
    $ud = $req['used_date'] ?? '';
    $hrs = isset($req['hours']) ? number_format((float)$req['hours'], 1) : '';
    $body = ($ud ? ($ud . ' ') : '') . ($hrs !== '' ? ($hrs . 'h ') : '') . '申請の決裁結果です。';
    $link = '../attendance_list.html';
    $ins = $pdo->prepare('INSERT INTO notifications (user_id, type, title, body, link) VALUES (?,?,?,?,?)');
    $ins->execute([(int)$req['user_id'], 'leave_request_result', $title, $body, $link]);
  } catch (Throwable $e) {
  }

  echo json_encode(['ok' => true, 'status' => $newStatus]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error' => 'db error']);
}
