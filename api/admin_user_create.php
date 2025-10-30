<?php
// api/admin_user_create.php
session_start();
header('Content-Type: application/json');
require_once '../db_config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'not logged in']);
    exit;
}
$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare('SELECT role FROM users WHERE id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user || $user['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'forbidden']);
    exit;
}
// POSTでユーザー名・権限・use_vehicle・contract_hours_per_dayを受け取る
$data = json_decode(file_get_contents('php://input'), true);
$name = $data['name'] ?? null;
$role = $data['role'] ?? 'user';
$use_vehicle = array_key_exists('use_vehicle', $data) ? (int)$data['use_vehicle'] : 1;
$has_contract_hours = array_key_exists('contract_hours_per_day', $data);
$raw_contract = $has_contract_hours ? $data['contract_hours_per_day'] : null;
// Stage B: optional scheduled pattern fields
$sched_wd = array_key_exists('scheduled_weekly_days', $data) ? $data['scheduled_weekly_days'] : null;
$sched_wh = array_key_exists('scheduled_weekly_hours', $data) ? $data['scheduled_weekly_hours'] : null;
$sched_ad = array_key_exists('scheduled_annual_days', $data) ? $data['scheduled_annual_days'] : null;
// full_time flag (employment type): 1=常勤, 0=パート/その他。未指定は1（常勤）
$full_time = array_key_exists('full_time', $data) ? ((int)$data['full_time'] === 1 ? 1 : 0) : 1;
if ($has_contract_hours) {
    if ($raw_contract === '' || $raw_contract === null || (is_numeric($raw_contract) && (float)$raw_contract <= 0)) {
        $contract_hours_per_day = null; // 未設定扱い
    } else {
        $contract_hours_per_day = (float)$raw_contract;
    }
} else {
    $contract_hours_per_day = null; // 省略時も未設定にする
}
if (!$name) {
    http_response_code(400);
    echo json_encode(['error' => 'name required']);
    exit;
}
// ユーザー名重複チェック
$stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE name = ? AND visible = 1');
$stmt->execute([$name]);
if ($stmt->fetchColumn() > 0) {
    http_response_code(409);
    echo json_encode(['error' => 'duplicate name']);
    exit;
}
$hash = password_hash('netone', PASSWORD_DEFAULT);

try {
    $pdo->beginTransaction();
    // users に作成（use_vehicle等は user_detail に分離）
    $stmt = $pdo->prepare('INSERT INTO users (name, role, password_hash, visible, must_reset_password) VALUES (?, ?, ?, 1, 1)');
    $stmt->execute([$name, $role, $hash]);
    $newUserId = (int)$pdo->lastInsertId();
    // Normalize scheduled fields
    $svd = ($sched_wd === '' || $sched_wd === null || (is_numeric($sched_wd) && (int)$sched_wd <= 0)) ? null : (int)$sched_wd;
    $svh = ($sched_wh === '' || $sched_wh === null || (is_numeric($sched_wh) && (float)$sched_wh <= 0)) ? null : (float)$sched_wh;
    $sad = ($sched_ad === '' || $sched_ad === null || (is_numeric($sched_ad) && (int)$sched_ad <= 0)) ? null : (int)$sched_ad;
    // user_detail に初期値で作成
    $stmt = $pdo->prepare('INSERT INTO user_detail (user_id, use_vehicle, contract_hours_per_day, full_time, scheduled_weekly_days, scheduled_weekly_hours, scheduled_annual_days) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$newUserId, $use_vehicle, $contract_hours_per_day, $full_time, $svd, $svh, $sad]);
    $pdo->commit();
    echo json_encode(['success' => true, 'id' => $newUserId]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'db error']);
}
