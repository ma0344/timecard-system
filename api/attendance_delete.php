<?php
/*
 * 目的: 指定日の勤務記録を削除します。
 * 入力: 対象ユーザー・対象日
 * 出力: 削除結果（成功/失敗）
 */
?>
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
$session_user_id = $_SESSION['user_id'];

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
  // 管理者権限の確認とターゲットユーザーの決定
  $stmtRole = $pdo->prepare('SELECT role FROM users WHERE id = ?');
  $stmtRole->execute([$session_user_id]);
  $me = $stmtRole->fetch(PDO::FETCH_ASSOC);
  $isAdmin = $me && $me['role'] === 'admin';

  $target_user_id = $session_user_id;
  if ($isAdmin && isset($input['user_id']) && $input['user_id']) {
    $target_user_id = (int)$input['user_id'];
  }

  // 対象日の全ての timecard のID取得（重複含む）
  $stmt_ids = $pdo->prepare('SELECT id FROM timecards WHERE user_id = ? AND work_date = ?');
  $stmt_ids->execute([$target_user_id, $date]);
  $rows = $stmt_ids->fetchAll(PDO::FETCH_COLUMN, 0);
  if ($rows && count($rows) > 0) {
    $ids = array_map('intval', $rows);
    $in = implode(',', array_fill(0, count($ids), '?'));
    // 関連休憩物理削除
    $sqlBreaks = "DELETE FROM breaks WHERE timecard_id IN ($in)";
    $stmt2 = $pdo->prepare($sqlBreaks);
    $stmt2->execute($ids);
    // 勤務記録物理削除
    $sqlTc = "DELETE FROM timecards WHERE id IN ($in)";
    $stmt = $pdo->prepare($sqlTc);
    $stmt->execute($ids);
  }
  echo json_encode(['message' => '削除しました', 'soft' => false]);
} catch (PDOException $e) {
  http_response_code(500);
  echo json_encode(['message' => '削除に失敗しました']);
}
