<?php
// api/leave_requests_approve_link.php
// 承認フォーム表示用の情報取得（トークン検証）
header('Content-Type: application/json');
require_once '../db_config.php';

$token = isset($_GET['token']) ? trim((string)$_GET['token']) : '';
if ($token === '') {
    http_response_code(400);
    echo json_encode(['error' => 'token required']);
    exit;
}

try {
    // テーブル存在しない場合に備えつつ問い合わせ
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

    $stmt = $pdo->prepare('SELECT lr.*, u.name FROM leave_requests lr JOIN users u ON u.id = lr.user_id WHERE lr.approve_token = ? LIMIT 1');
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
    echo json_encode([
        'ok' => true,
        'request_id' => (int)$req['id'],
        'name' => $req['name'],
        'used_date' => $req['used_date'],
        'hours' => (float)$req['hours'],
        'reason' => $req['reason'],
        'status' => $req['status']
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'db error']);
}
