<?php
// api/paid_leave_use_event_update.php
// USEイベントの編集（used_date/used_hours/reason）。既存割当を戻して再割当し直す。
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
$newDate = isset($data['used_date']) ? $data['used_date'] : null;
$newHours = isset($data['used_hours']) ? (float)$data['used_hours'] : null;
$newReason = isset($data['reason']) ? trim($data['reason']) : null;

if ($eventId <= 0 || $userId <= 0 || !$newDate || $newHours === null || $newHours <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'event_id, user_id, used_date, used_hours required']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 失効ID
    $logTypeExpire = (int)($pdo->query("SELECT id FROM log_types WHERE code = 'EXPIRE' LIMIT 1")->fetchColumn() ?: 3);
    $logTypeUse = (int)($pdo->query("SELECT id FROM log_types WHERE code = 'USE' LIMIT 1")->fetchColumn() ?: 2);

    // 既存イベントの割当を戻す
    $q = $pdo->prepare('SELECT id, paid_leave_id, used_hours FROM paid_leave_logs WHERE user_id = ? AND event_id = ? AND (log_type_id IS NULL OR log_type_id <> ?)');
    $q->execute([$userId, $eventId, $logTypeExpire]);
    $olds = $q->fetchAll(PDO::FETCH_ASSOC);
    foreach ($olds as $l) {
        if ($l['paid_leave_id']) {
            $pdo->prepare('UPDATE paid_leaves SET consumed_hours_total = ROUND(GREATEST(consumed_hours_total - ?, 0), 2) WHERE id = ?')
                ->execute([(float)$l['used_hours'], (int)$l['paid_leave_id']]);
        }
    }
    $pdo->prepare('DELETE FROM paid_leave_logs WHERE user_id = ? AND event_id = ?')->execute([$userId, $eventId]);

    // 割当やり直し（期限NULL含め有効、used_date時点のFIFO）
    $needed = $newHours;
    // 設定: マイナス残許可
    $st = $pdo->prepare('SELECT COALESCE(negative_balance_allowed, 0) AS allow_negative FROM user_leave_settings WHERE user_id = ?');
    $st->execute([$userId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    $allowNegative = $row ? ((int)$row['allow_negative'] === 1) : false;
    $alloc = $pdo->prepare('SELECT id, grant_date, grant_hours, consumed_hours_total, expire_date FROM paid_leaves WHERE user_id = ? AND (expire_date IS NULL OR expire_date > ?) AND grant_date <= ? AND (grant_hours - consumed_hours_total) > 0 ORDER BY grant_date ASC, id ASC');
    $alloc->execute([$userId, $newDate, $newDate]);
    $activeImpactToday = 0.0;
    while ($needed > 1e-9 && ($g = $alloc->fetch(PDO::FETCH_ASSOC))) {
        $rem = (float)$g['grant_hours'] - (float)$g['consumed_hours_total'];
        if ($rem <= 0) continue;
        $take = min($rem, $needed);
        $pdo->prepare('INSERT INTO paid_leave_logs (user_id, paid_leave_id, event_id, used_date, used_hours, reason, log_type_id) VALUES (?, ?, ?, ?, ?, ?, ?)')
            ->execute([$userId, (int)$g['id'], $eventId, $newDate, $take, $newReason, $logTypeUse]);
        $pdo->prepare('UPDATE paid_leaves SET consumed_hours_total = ROUND(consumed_hours_total + ?, 2) WHERE id = ?')
            ->execute([$take, (int)$g['id']]);
        // 今日時点の未失効なら残高への影響に含める（後ほどrecalcで正しくなるが一応整合）
        $chk = $pdo->prepare('SELECT (CASE WHEN (expire_date IS NULL OR DATE(expire_date) > CURDATE()) THEN 1 ELSE 0 END) FROM paid_leaves WHERE id = ?');
        $chk->execute([(int)$g['id']]);
        if ((int)$chk->fetchColumn() === 1) $activeImpactToday += $take;
        $needed -= $take;
    }
    if ($needed > 1e-9 && !$allowNegative) {
        // 許可されていない場合はエラー
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(['error' => 'insufficient balance']);
        return;
    }
    if ($needed > 1e-9) {
        // 許容：未充当分（マイナス残は別設定で制御。ここでは記録のみ）
        $pdo->prepare('INSERT INTO paid_leave_logs (user_id, paid_leave_id, event_id, used_date, used_hours, reason, log_type_id) VALUES (?, NULL, ?, ?, ?, ?, ?)')
            ->execute([$userId, $eventId, $newDate, $needed, $newReason, $logTypeUse]);
    }

    // イベントメタ更新
    $pdo->prepare('UPDATE paid_leave_use_events SET used_date = ?, total_hours = ?, reason = ? WHERE id = ? AND user_id = ?')
        ->execute([$newDate, $newHours, $newReason, $eventId, $userId]);

    // サマリ再計算
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

    // 失効ログ補正（バックフィル相当）: 残が0なら既存EXPIREを削除、残>0ならEXPIREを更新/作成
    try {
        $today = date('Y-m-d');
        $q = $pdo->prepare('SELECT id, grant_hours, consumed_hours_total, expire_date FROM paid_leaves WHERE user_id = ? AND expire_date IS NOT NULL AND expire_date <= ?');
        $q->execute([$userId, $today]);
        while ($g = $q->fetch(PDO::FETCH_ASSOC)) {
            $rem = max(0.0, (float)$g['grant_hours'] - (float)$g['consumed_hours_total']);
            $expDate = $g['expire_date'];
            $dup = $pdo->prepare('SELECT id FROM paid_leave_logs WHERE paid_leave_id = ? AND used_date = ? AND log_type_id = ? LIMIT 1');
            $dup->execute([(int)$g['id'], $expDate, $logTypeExpire]);
            $exist = $dup->fetch(PDO::FETCH_ASSOC);
            if ($rem > 0) {
                if ($exist) {
                    $pdo->prepare('UPDATE paid_leave_logs SET used_hours = ? WHERE id = ?')->execute([$rem, (int)$exist['id']]);
                } else {
                    $pdo->prepare('INSERT INTO paid_leave_logs (user_id, paid_leave_id, used_date, used_hours, reason, log_type_id) VALUES (?, ?, ?, ?, ?, ?)')
                        ->execute([$userId, (int)$g['id'], $expDate, $rem, '失効', $logTypeExpire]);
                }
            } else {
                if ($exist) {
                    $pdo->prepare('DELETE FROM paid_leave_logs WHERE id = ?')->execute([(int)$exist['id']]);
                }
            }
        }
    } catch (Exception $e) {
    }

    // 監査
    try {
        $pdo->prepare('INSERT INTO audit_logs (actor_user_id, target_user_id, action, details) VALUES (?, ?, ?, JSON_OBJECT("event_id", ?, "used_date", ?, "used_hours", ?, "reason", ?))')
            ->execute([$actorId, $userId, 'paid_leave_use_event_update', $eventId, $newDate, $newHours, $newReason]);
    } catch (Exception $e) {
    }

    $pdo->commit();
    echo json_encode(['ok' => true]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'db error']);
}
