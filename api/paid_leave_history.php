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
