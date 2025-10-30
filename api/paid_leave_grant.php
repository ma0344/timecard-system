<?php
// api/paid_leave_grant.php
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
$grantDate = $data['grant_date'] ?? null;
$grantHours = isset($data['grant_hours']) ? (float)$data['grant_hours'] : null;
$expireDate = $data['expire_date'] ?? null; // null/'' も許容（''はnull扱い）
if ($expireDate === '') {
    $expireDate = null;
}

if (!$targetId || !$grantDate || $grantHours === null) {
    http_response_code(400);
    echo json_encode(['error' => 'user_id, grant_date, grant_hours required']);
    exit;
}

// ユーザー存在確認
$stmt = $pdo->prepare('SELECT id FROM users WHERE id = ?');
$stmt->execute([$targetId]);
if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
    http_response_code(404);
    echo json_encode(['error' => 'user not found']);
    exit;
}

try {
    // 全社設定の既定（月数）を取得
    $months = 24; // fallback
    try {
        $stmt = $pdo->query('SELECT paid_leave_valid_months FROM settings ORDER BY id DESC LIMIT 1');
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $months = (int)($row['paid_leave_valid_months'] ?? 24);
        }
    } catch (Exception $e) {
        // ignore and use fallback
    }
    // 期限指定なしの場合は、設定に従って決定（<=0 の場合は無期限=NULL）
    if ($expireDate === null) {
        if ($months > 0) {
            // 月加算（末日補正）＋ 前日を満了日とする
            $dt = new DateTime($grantDate);
            $origDay = (int)$dt->format('d');
            $dt->modify('+' . $months . ' months');
            if ((int)$dt->format('d') < $origDay) {
                // PHPの+monthsで月末を超えた場合、翌月に進むことがあるため、前月末日に補正
                $dt->modify('last day of previous month');
            }
            // 満了日は「+months」の前日
            $dt->modify('-1 day');
            $expireDate = $dt->format('Y-m-d');
        } else {
            $expireDate = null; // 無期限
        }
    }
    $pdo->beginTransaction();
    // paid_leaves: consumed_hours_total はデフォルト0
    $stmt = $pdo->prepare('INSERT INTO paid_leaves (user_id, grant_date, grant_hours, expire_date) VALUES (?, ?, ?, ?)');
    $stmt->execute([$targetId, $grantDate, $grantHours, $expireDate]);
    $id = $pdo->lastInsertId();

    // サマリ行を用意
    $pdo->prepare('INSERT IGNORE INTO user_leave_summary (user_id, balance_hours, used_total_hours, next_expire_date) VALUES (?, 0, 0, NULL)')->execute([$targetId]);
    // 付与が「今日時点で有効」なら残高に反映（usedは関係なし）
    $isActiveToday = ($expireDate === null) ? true : (strtotime($expireDate) > strtotime('today'));
    if ($isActiveToday) {
        // balance を増加
        $pdo->prepare('UPDATE user_leave_summary SET balance_hours = ROUND(balance_hours + ?, 2) WHERE user_id = ?')->execute([$grantHours, $targetId]);
        // next_expire_date を更新（無期限のときは変更しない）
        if ($expireDate !== null) {
            $pdo->prepare('UPDATE user_leave_summary SET next_expire_date = (CASE WHEN next_expire_date IS NULL OR next_expire_date > ? THEN ? ELSE next_expire_date END) WHERE user_id = ?')
                ->execute([$expireDate, $expireDate, $targetId]);
        }
    }
    $pdo->commit();
    echo json_encode(['success' => true, 'id' => (int)$id]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => 'db error']);
}
