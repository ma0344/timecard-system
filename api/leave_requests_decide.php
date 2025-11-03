<?php
// api/leave_requests_decide.php
// トークンでの承認/却下（最小実装：ログイン不要、ワンタイムトークン）
header('Content-Type: application/json');
require_once '../db_config.php';

$data = $_POST;
if (empty($data)) {
    // JSON でも受け付け
    $raw = json_decode(file_get_contents('php://input'), true);
    if (is_array($raw)) $data = $raw;
}

$token = isset($data['token']) ? trim((string)$data['token']) : '';
$action = isset($data['action']) ? trim((string)$data['action']) : '';
if ($token === '' || !in_array($action, ['approve', 'reject'], true)) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid params']);
    exit;
}

try {
    $pdo->exec('CREATE TABLE IF NOT EXISTS leave_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        used_date DATE NOT NULL,
        hours DECIMAL(6,2) NOT NULL,
        reason TEXT NULL,
        status ENUM("pending","approved","rejected") NOT NULL DEFAULT "pending",
        approver_user_id INT NULL,
        decided_at DATETIME NULL,
        approve_token VARCHAR(128) UNIQUE,
        approve_token_expires_at DATETIME NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX (status), INDEX (approve_token)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

    // 有効なトークンか確認
    $stmt = $pdo->prepare('SELECT id, status, approve_token_expires_at FROM leave_requests WHERE approve_token = ? LIMIT 1');
    $stmt->execute([$token]);
    $req = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$req) {
        http_response_code(404);
        echo json_encode(['error' => 'not found']);
        exit;
    }
    if ($req['approve_token_expires_at'] && strtotime($req['approve_token_expires_at']) < time()) {
        http_response_code(410);
        echo json_encode(['error' => 'token expired']);
        exit;
    }
    if ($req['status'] !== 'pending') {
        echo json_encode(['warning' => 'already decided', 'status' => $req['status']]);
        exit;
    }

    $newStatus = $action === 'approve' ? 'approved' : 'rejected';
    $upd = $pdo->prepare('UPDATE leave_requests SET status = ?, decided_at = NOW(), approve_token = NULL, approve_token_expires_at = NULL WHERE id = ?');
    $upd->execute([$newStatus, (int)$req['id']]);
    echo json_encode(['ok' => true, 'status' => $newStatus]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'db error']);
}
