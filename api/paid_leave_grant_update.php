<?php
// api/paid_leave_grant_update.php
// 管理者が付与(paid_leaves)を編集するAPI。編集後は当該ユーザーのサマリを再計算する。
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

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$grantId = isset($input['id']) ? (int)$input['id'] : 0;
$grantDate = isset($input['grant_date']) ? trim($input['grant_date']) : null;
$grantHours = isset($input['grant_hours']) ? (float)$input['grant_hours'] : null;
// expire_date の取り扱い: キーが存在しない場合は「変更しない」。空文字は NULL（無期限）。
$hasExpire = array_key_exists('expire_date', $input);
$expireDate = $hasExpire ? (trim((string)$input['expire_date']) !== '' ? trim((string)$input['expire_date']) : null) : null;

if ($grantId <= 0 || !$grantDate || $grantHours === null) {
    http_response_code(400);
    echo json_encode(['error' => 'id, grant_date, grant_hours required']);
    exit;
}

try {
    $pdo->beginTransaction();
    // 既存取得
    $stmt = $pdo->prepare('SELECT user_id, grant_hours, consumed_hours_total FROM paid_leaves WHERE id = ? FOR UPDATE');
    $stmt->execute([$grantId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        throw new Exception('not found');
    }
    $userId = (int)$row['user_id'];
    $consumed = (float)$row['consumed_hours_total'];

    if ($grantHours < $consumed - 1e-9) {
        throw new Exception('grant_hours cannot be less than consumed_hours_total');
    }

    // 更新（expire_date 未指定の場合は現状維持）
    if ($hasExpire) {
        $upd = $pdo->prepare('UPDATE paid_leaves SET grant_date = ?, grant_hours = ?, expire_date = ? WHERE id = ?');
        $upd->execute([$grantDate, $grantHours, $expireDate, $grantId]);
    } else {
        $upd = $pdo->prepare('UPDATE paid_leaves SET grant_date = ?, grant_hours = ? WHERE id = ?');
        $upd->execute([$grantDate, $grantHours, $grantId]);
    }

    // 再計算（サマリ＆割当）
    // 直接includeも可能だが、ここでは同等ロジックを簡便に呼ぶためAPI相当の処理をインラインで行うか、
    // 既存の再計算APIのクエリをそのまま実行する。
    // -> 既存の再計算APIを関数化していないため、ここでは最低限: consumedをリセットし、USEログ再割当→サマリ更新。

    // per-grant消化をリセット
    $pdo->prepare('UPDATE paid_leaves SET consumed_hours_total = 0 WHERE user_id = ?')->execute([$userId]);

    // EXPIREログIDを識別
    $logTypeExpire = null;
    $lt = $pdo->query("SELECT id FROM log_types WHERE code = 'EXPIRE' LIMIT 1");
    if ($lt) {
        $logTypeExpire = $lt->fetchColumn();
    }
    if (!$logTypeExpire) {
        $logTypeExpire = 3;
    }

    // USEログをused_date昇順で再割当（EXPIREは除外）
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
            $pdo->prepare('UPDATE paid_leaves SET consumed_hours_total = ROUND(consumed_hours_total + ?, 2) WHERE id = ?')->execute([$take, (int)$g['id']]);
            $needed -= $take;
        }
    }

    // サマリ更新
    $pdo->prepare('INSERT IGNORE INTO user_leave_summary (user_id, balance_hours, used_total_hours, next_expire_date) VALUES (?, 0, 0, NULL)')
        ->execute([$userId]);
    $stmt = $pdo->prepare('SELECT ROUND(IFNULL(SUM(GREATEST(grant_hours - consumed_hours_total, 0)),0),2) FROM paid_leaves WHERE user_id = ? AND (expire_date IS NULL OR expire_date > CURDATE())');
    $stmt->execute([$userId]);
    $balance = (float)$stmt->fetchColumn();
    $stmt = $pdo->prepare('SELECT MIN(expire_date) FROM paid_leaves WHERE user_id = ? AND expire_date > CURDATE() AND (grant_hours - consumed_hours_total) > 0');
    $stmt->execute([$userId]);
    $next = $stmt->fetchColumn();
    $stmt = $pdo->prepare('SELECT ROUND(IFNULL(SUM(used_hours),0),2) FROM paid_leave_logs WHERE user_id = ?');
    $stmt->execute([$userId]);
    $usedTotal = (float)$stmt->fetchColumn();
    $pdo->prepare('UPDATE user_leave_summary SET balance_hours = ?, used_total_hours = ?, next_expire_date = ? WHERE user_id = ?')
        ->execute([$balance, $usedTotal, $next ?: null, $userId]);

    $pdo->commit();
    echo json_encode(['ok' => true, 'id' => $grantId, 'user_id' => $userId]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage() ?: 'update failed']);
}
