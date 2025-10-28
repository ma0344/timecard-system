<?php
// api/paid_leave_use.php
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

$data = json_decode(file_get_contents('php://input'), true);
$targetId = isset($data['user_id']) ? (int)$data['user_id'] : null;
$usedDate = $data['used_date'] ?? null;
$usedHours = isset($data['used_hours']) ? (float)$data['used_hours'] : null;
$reason = isset($data['reason']) ? trim($data['reason']) : null;

if (!$targetId || !$usedDate || $usedHours === null) {
    http_response_code(400);
    echo json_encode(['error' => 'user_id, used_date, used_hours required']);
    exit;
}
if (!is_numeric($usedHours) || $usedHours <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'used_hours must be > 0']);
    exit;
}

// マイナス残高許可の取得（未設定なら0）
$stmt = $pdo->prepare('SELECT COALESCE(negative_balance_allowed, 0) AS allow_negative FROM user_leave_settings WHERE user_id = ?');
$stmt->execute([$targetId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$allowNegative = $row ? ((int)$row['allow_negative'] === 1) : false;

// サマリ行を用意
$pdo->prepare('INSERT IGNORE INTO user_leave_summary (user_id, balance_hours, used_total_hours, next_expire_date) VALUES (?, 0, 0, NULL)')->execute([$targetId]);

try {
    $pdo->beginTransaction();
    // ログタイプ（USE）を取得
    $logTypeId = null;
    $stmt = $pdo->prepare("SELECT id FROM log_types WHERE code = 'USE' LIMIT 1");
    if ($stmt->execute()) {
        $logTypeId = $stmt->fetchColumn();
    }
    if (!$logTypeId) {
        $logTypeId = 2;
    }

    // イベント作成（この使用一式の識別子）
    $pdo->prepare('INSERT INTO paid_leave_use_events (user_id, used_date, total_hours, reason) VALUES (?, ?, ?, ?)')->execute([$targetId, $usedDate, $usedHours, $reason]);
    $eventId = (int)$pdo->lastInsertId();

    // FIFO割当（used_date 時点で有効な付与にのみ充当、期限NULLは有効扱い）
    $needed = $usedHours;
    $allocations = [];
    $stmt = $pdo->prepare('SELECT id, grant_date, grant_hours, consumed_hours_total, expire_date FROM paid_leaves WHERE user_id = ? AND (expire_date IS NULL OR expire_date > ?) AND grant_date <= ? AND (grant_hours - consumed_hours_total) > 0 ORDER BY grant_date ASC, id ASC');
    $stmt->execute([$targetId, $usedDate, $usedDate]);
    while ($needed > 1e-9 && ($row = $stmt->fetch(PDO::FETCH_ASSOC))) {
        $remaining = (float)$row['grant_hours'] - (float)$row['consumed_hours_total'];
        if ($remaining <= 0) continue;
        $take = min($remaining, $needed);
        $allocations[] = [
            'paid_leave_id' => (int)$row['id'],
            'expire_date' => $row['expire_date'],
            'hours' => $take,
        ];
        $needed -= $take;
    }

    if ($needed > 1e-9 && !$allowNegative) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(['error' => 'insufficient balance']);
        exit;
    }

    // ログと残量更新
    $activeImpactToday = 0.0; // 本日未失効分への充当のみ残高に反映
    foreach ($allocations as $a) {
        // per-grant log
        $ins = $pdo->prepare('INSERT INTO paid_leave_logs (user_id, paid_leave_id, event_id, used_date, used_hours, reason, log_type_id) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $ins->execute([$targetId, $a['paid_leave_id'], $eventId, $usedDate, $a['hours'], $reason, $logTypeId]);
        // consume
        $upd = $pdo->prepare('UPDATE paid_leaves SET consumed_hours_total = ROUND(consumed_hours_total + ?, 2) WHERE id = ?');
        $upd->execute([$a['hours'], $a['paid_leave_id']]);
        // 今日時点で未失効なら残高反映
        $chk = $pdo->prepare('SELECT (CASE WHEN DATE(?) > CURDATE() THEN 1 ELSE 0 END)');
        $chk->execute([$a['expire_date']]);
        if ((int)$chk->fetchColumn() === 1) {
            $activeImpactToday += $a['hours'];
        }
    }

    // 未充当分（許容時）は grant_id = NULL で1本ログ
    if ($needed > 1e-9) {
        $pdo->prepare('INSERT INTO paid_leave_logs (user_id, paid_leave_id, event_id, used_date, used_hours, reason, log_type_id) VALUES (?, NULL, ?, ?, ?, ?, ?)')
            ->execute([$targetId, $eventId, $usedDate, $needed, $reason, $logTypeId]);
        // 残高は未失効分への充当のみ減らす方針のため、needed分は balance には反映しない
    }

    // サマリ更新: balance（今日時点で未失効への充当分のみ減算）, used_total_hours は総量加算
    $pdo->prepare('UPDATE user_leave_summary SET balance_hours = ROUND(balance_hours - ?, 2), used_total_hours = ROUND(used_total_hours + ?, 2) WHERE user_id = ?')
        ->execute([$activeImpactToday, $usedHours, $targetId]);

    // next_expire_date を最小の未失効・未消化付与の expire に更新
    $minStmt = $pdo->prepare('SELECT MIN(expire_date) FROM paid_leaves WHERE user_id = ? AND expire_date > CURDATE() AND (grant_hours - consumed_hours_total) > 0');
    $minStmt->execute([$targetId]);
    $next = $minStmt->fetchColumn();
    $pdo->prepare('UPDATE user_leave_summary SET next_expire_date = ? WHERE user_id = ?')->execute([$next ?: null, $targetId]);

    $pdo->commit();
    // 監査ログ
    try {
        $pdo->prepare('INSERT INTO audit_logs (actor_user_id, target_user_id, action, details) VALUES (?, ?, ?, JSON_OBJECT("event_id", ?, "used_date", ?, "used_hours", ?, "reason", ?))')
            ->execute([$adminId, $targetId, 'paid_leave_use_create', $eventId, $usedDate, $usedHours, $reason]);
    } catch (Exception $e) {
    }

    // 追加: 再計算 → 失効ログの補正（バックフィル相当）
    try {
        // サマリ再計算（EXPIRE除外、期限NULLは有効）
        $pdo->beginTransaction();
        // ログタイプ: EXPIRE
        $logTypeExpire = (int)($pdo->query("SELECT id FROM log_types WHERE code = 'EXPIRE' LIMIT 1")->fetchColumn() ?: 3);
        $pdo->prepare('INSERT IGNORE INTO user_leave_summary (user_id, balance_hours, used_total_hours, next_expire_date) VALUES (?, 0, 0, NULL)')->execute([$targetId]);
        $pdo->prepare('UPDATE paid_leaves SET consumed_hours_total = 0 WHERE user_id = ?')->execute([$targetId]);
        $logs = $pdo->prepare('SELECT used_date, used_hours FROM paid_leave_logs WHERE user_id = ? AND used_hours > 0 AND (log_type_id IS NULL OR log_type_id <> ?) ORDER BY used_date ASC, id ASC');
        $logs->execute([$targetId, $logTypeExpire]);
        while ($log = $logs->fetch(PDO::FETCH_ASSOC)) {
            $need = (float)$log['used_hours'];
            $u = $log['used_date'];
            $gr = $pdo->prepare('SELECT id, grant_hours, consumed_hours_total FROM paid_leaves WHERE user_id = ? AND (expire_date IS NULL OR expire_date > ?) AND grant_date <= ? AND (grant_hours - consumed_hours_total) > 0 ORDER BY grant_date ASC, id ASC');
            $gr->execute([$targetId, $u, $u]);
            while ($need > 1e-9 && ($gg = $gr->fetch(PDO::FETCH_ASSOC))) {
                $rem = (float)$gg['grant_hours'] - (float)$gg['consumed_hours_total'];
                if ($rem <= 0) continue;
                $tk = min($rem, $need);
                $pdo->prepare('UPDATE paid_leaves SET consumed_hours_total = ROUND(consumed_hours_total + ?, 2) WHERE id = ?')->execute([$tk, (int)$gg['id']]);
                $need -= $tk;
            }
        }
        // balance/next/used_total
        $stmt = $pdo->prepare('SELECT ROUND(IFNULL(SUM(GREATEST(grant_hours - consumed_hours_total, 0)),0),2) FROM paid_leaves WHERE user_id = ? AND (expire_date IS NULL OR expire_date > CURDATE())');
        $stmt->execute([$targetId]);
        $balance = (float)$stmt->fetchColumn();
        $stmt = $pdo->prepare('SELECT MIN(expire_date) FROM paid_leaves WHERE user_id = ? AND expire_date IS NOT NULL AND expire_date > CURDATE() AND (grant_hours - consumed_hours_total) > 0');
        $stmt->execute([$targetId]);
        $next = $stmt->fetchColumn();
        $stmt = $pdo->prepare('SELECT ROUND(IFNULL(SUM(used_hours),0),2) FROM paid_leave_logs WHERE user_id = ? AND (log_type_id IS NULL OR log_type_id <> ?)');
        $stmt->execute([$targetId, $logTypeExpire]);
        $usedTotal = (float)$stmt->fetchColumn();
        $pdo->prepare('UPDATE user_leave_summary SET balance_hours = ?, used_total_hours = ?, next_expire_date = ? WHERE user_id = ?')->execute([$balance, $usedTotal, $next ?: null, $targetId]);

        // 失効ログ補正（バックフィル相当）: 残が0なら既存EXPIREを削除、残>0ならEXPIREを更新/作成
        $today = date('Y-m-d');
        $q = $pdo->prepare('SELECT id, grant_hours, consumed_hours_total, expire_date FROM paid_leaves WHERE user_id = ? AND expire_date IS NOT NULL AND expire_date <= ?');
        $q->execute([$targetId, $today]);
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
                        ->execute([$targetId, (int)$g['id'], $expDate, $rem, '失効', $logTypeExpire]);
                }
            } else {
                if ($exist) {
                    $pdo->prepare('DELETE FROM paid_leave_logs WHERE id = ?')->execute([(int)$exist['id']]);
                }
            }
        }
        $pdo->commit();
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
    }

    echo json_encode(['success' => true, 'event_id' => $eventId]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => 'db error']);
}
