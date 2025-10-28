<?php
// api/paid_leave_use_delete.php
// 管理者が取得(paid_leave_logsのUSE)を削除するAPI。EXPIREは対象外。削除後は再計算。
session_start();
header('Content-Type: application/json');
require_once '../db_config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'not logged in']);
    exit;
}

$adminId = $_SESSION['user_id'];
$stmt = $pdo->prepare('SELECT role FROM users WHERE id = ?');
$stmt->execute([$adminId]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$admin || $admin['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'forbidden']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$logId = isset($input['id']) ? (int)$input['id'] : 0;
if ($logId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'id required']);
    exit;
}

try {
    $pdo->beginTransaction();
    // ログの存在と種別チェック
    $stmt = $pdo->prepare('SELECT user_id, log_type_id FROM paid_leave_logs WHERE id = ? FOR UPDATE');
    $stmt->execute([$logId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) throw new Exception('not found');
    $userId = (int)$row['user_id'];

    $logTypeExpire = null;
    $lt = $pdo->query("SELECT id FROM log_types WHERE code = 'EXPIRE' LIMIT 1");
    if ($lt) {
        $logTypeExpire = $lt->fetchColumn();
    }
    if (!$logTypeExpire) {
        $logTypeExpire = 3;
    }

    if ((int)$row['log_type_id'] === (int)$logTypeExpire) {
        throw new Exception('cannot delete EXPIRE log');
    }

    // 削除
    $pdo->prepare('DELETE FROM paid_leave_logs WHERE id = ?')->execute([$logId]);

    // 再計算
    $pdo->prepare('UPDATE paid_leaves SET consumed_hours_total = 0 WHERE user_id = ?')->execute([$userId]);

    $logs = $pdo->prepare('SELECT id, used_date, used_hours FROM paid_leave_logs WHERE user_id = ? AND used_hours > 0 AND (log_type_id IS NULL OR log_type_id <> ?) ORDER BY used_date ASC, id ASC');
    $logs->execute([$userId, $logTypeExpire]);
    while ($log = $logs->fetch(PDO::FETCH_ASSOC)) {
        $needed = (float)$log['used_hours'];
        $usedDate = $log['used_date'];
        if ($needed <= 0) continue;
        $gr = $pdo->prepare('SELECT id, grant_hours, consumed_hours_total FROM paid_leaves WHERE user_id = ? AND expire_date > ? AND grant_date <= ? AND (grant_hours - consumed_hours_total) > 0 ORDER BY grant_date ASC, id ASC');
        $gr->execute([$userId, $usedDate, $usedDate]);
        while ($needed > 1e-9 && ($g = $gr->fetch(PDO::FETCH_ASSOC))) {
            $rem = (float)$g['grant_hours'] - (float)$g['consumed_hours_total'];
            if ($rem <= 0) continue;
            $take = min($rem, $needed);
            $pdo->prepare('UPDATE paid_leaves SET consumed_hours_total = ROUND(consumed_hours_total + ?, 2) WHERE id = ?')->execute([$take, (int)$g['id']]);
            $needed -= $take;
        }
    }

    $pdo->prepare('INSERT IGNORE INTO user_leave_summary (user_id, balance_hours, used_total_hours, next_expire_date) VALUES (?, 0, 0, NULL)')->execute([$userId]);
    $stmt = $pdo->prepare('SELECT ROUND(IFNULL(SUM(GREATEST(grant_hours - consumed_hours_total, 0)),0),2) FROM paid_leaves WHERE user_id = ? AND expire_date > CURDATE()');
    $stmt->execute([$userId]);
    $balance = (float)$stmt->fetchColumn();
    $stmt = $pdo->prepare('SELECT MIN(expire_date) FROM paid_leaves WHERE user_id = ? AND expire_date > CURDATE() AND (grant_hours - consumed_hours_total) > 0');
    $stmt->execute([$userId]);
    $next = $stmt->fetchColumn();
    $stmt = $pdo->prepare('SELECT ROUND(IFNULL(SUM(used_hours),0),2) FROM paid_leave_logs WHERE user_id = ?');
    $stmt->execute([$userId]);
    $usedTotal = (float)$stmt->fetchColumn();
    $pdo->prepare('UPDATE user_leave_summary SET balance_hours = ?, used_total_hours = ?, next_expire_date = ? WHERE user_id = ?')->execute([$balance, $usedTotal, $next ?: null, $userId]);

    $pdo->commit();
    echo json_encode(['ok' => true, 'id' => $logId, 'user_id' => $userId]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage() ?: 'delete failed']);
}
