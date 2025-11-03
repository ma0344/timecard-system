<?php
// api/leave_requests_pending.php
// 保留中の有休申請一覧（ダッシュボード用サマリ）
session_start();
header('Content-Type: application/json');
require_once '../db_config.php';

// 管理者のみ
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'not logged in']);
    exit;
}
$adminId = $_SESSION['user_id'];
$stmt = $pdo->prepare('SELECT role FROM users WHERE id = ?');
$stmt->execute([$adminId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row || $row['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'forbidden']);
    exit;
}

try {
    // leave_requests テーブルが未作成でも落ちないようにクエリを分離
    $sql = "
        SELECT lr.id, lr.user_id, u.name, lr.used_date, lr.hours, lr.reason, lr.status, lr.created_at
        FROM leave_requests lr
        JOIN users u ON u.id = lr.user_id
        WHERE lr.status = 'pending'
        ORDER BY lr.created_at ASC
        LIMIT 50
    ";
    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode([
        'ok' => true,
        'count' => count($rows),
        'items' => array_map(function ($r) {
            return [
                'id' => (int)$r['id'],
                'user_id' => (int)$r['user_id'],
                'name' => $r['name'],
                'used_date' => $r['used_date'],
                'hours' => isset($r['hours']) ? (float)$r['hours'] : null,
                'reason' => $r['reason'],
                'created_at' => $r['created_at']
            ];
        }, $rows)
    ]);
} catch (Throwable $e) {
    // テーブル未作成時は空で返す（ロードマップ通り、後続で実装）
    echo json_encode(['ok' => true, 'count' => 0, 'items' => []]);
}
