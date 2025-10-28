<?php
// api/admin_user_restore.php
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
// POSTでユーザーIDを受け取る（復元時は退職日を必ずクリア）
$data = json_decode(file_get_contents('php://input'), true);
$targetId = $data['id'] ?? null;
if (!$targetId) {
    http_response_code(400);
    echo json_encode(['error' => 'id required']);
    exit;
}
try {
    $pdo->beginTransaction();
    // 名前の末尾に削除サフィックスが付与されていれば除去し、重複がなければ元名に戻す
    $stmt = $pdo->prepare('SELECT name FROM users WHERE id = ? FOR UPDATE');
    $stmt->execute([$targetId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        throw new Exception('not found');
    }
    $name = $row['name'];
    // パターン: " (deleted YYYYMMDD-HHMMSS#<id>)" を末尾から除去
    $pattern = '/\s\(deleted\s\d{8}-\d{6}#' . preg_quote((string)$targetId, '/') . '\)$/';
    $baseName = preg_replace($pattern, '', $name);
    if ($baseName === null) {
        $baseName = $name;
    }
    // 同名アクティブがいない場合のみ名前を戻す
    $canRename = true;
    if ($baseName !== $name) {
        $chk = $pdo->prepare('SELECT COUNT(*) FROM users WHERE id <> ? AND visible = 1 AND name = ?');
        $chk->execute([$targetId, $baseName]);
        $canRename = ((int)$chk->fetchColumn() === 0);
    }
    if ($canRename && $baseName !== $name) {
        $stmt = $pdo->prepare('UPDATE users SET visible = 1, name = ? WHERE id = ?');
        $stmt->execute([$baseName, $targetId]);
    } else {
        $stmt = $pdo->prepare('UPDATE users SET visible = 1 WHERE id = ?');
        $stmt->execute([$targetId]);
    }
    // 復元時は retire_date を必ずクリア
    $stmt = $pdo->prepare('UPDATE user_detail SET retire_date = NULL WHERE user_id = ?');
    $stmt->execute([$targetId]);
    $pdo->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'db error']);
}
