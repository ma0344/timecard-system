<?php
// api/attendance_delete.php
header('Content-Type: application/json');
require_once __DIR__ . '/../db_config.php';

// POSTのみ許可
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['message' => 'Method Not Allowed']);
    exit;
}

// JSON受信
$input = json_decode(file_get_contents('php://input'), true);
if (!isset($input['date'])) {
    http_response_code(400);
    echo json_encode(['message' => '日付が指定されていません']);
    exit;
}
$date = $input['date'];

// ログインチェック（ユーザーID取得）
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['message' => '未ログインです']);
    exit;
}
$user_id = $_SESSION['user_id'];

// DB接続
try {
    // $pdo = new PDO(DB_DSN, DB_USER, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['message' => 'DB接続エラー']);
    exit;
}

// 勤務記録削除
try {
    // まず該当timecard_idを取得
    $stmt_id = $pdo->prepare('SELECT id FROM timecards WHERE user_id = ? AND work_date = ?');
    $stmt_id->execute([$user_id, $date]);
    $row = $stmt_id->fetch(PDO::FETCH_ASSOC);
    if ($row && isset($row['id'])) {
        $timecard_id = $row['id'];
        // 休憩記録を先に削除（外部キー制約対応）
        $stmt2 = $pdo->prepare('DELETE FROM breaks WHERE timecard_id = ?');
        $stmt2->execute([$timecard_id]);
        // 勤務記録本体削除
        $stmt = $pdo->prepare('DELETE FROM timecards WHERE id = ?');
        $stmt->execute([$timecard_id]);
    }
    echo json_encode(['message' => '削除しました']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['message' => '削除に失敗しました']);
}
