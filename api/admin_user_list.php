<?php
/*
 * 目的: 管理者がユーザー一覧を取得します。
 * 入力: ページング/検索フィルタ（任意）
 * 出力: ユーザー基本情報の一覧
 */
?>
<?php
// api/admin_user_list.php
session_start();
header('Content-Type: application/json');
require_once '../db_config.php';

// 管理者権限チェック
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

// ユーザー一覧取得（user_detail を左結合）
$sql = 'SELECT u.id, u.name, u.role, u.visible,
    COALESCE(d.use_vehicle, 1) AS use_vehicle,
    d.contract_hours_per_day AS contract_hours_per_day,
    d.hire_date AS hire_date,
    d.retire_date AS retire_date,
    d.scheduled_weekly_days AS scheduled_weekly_days,
    d.scheduled_weekly_hours AS scheduled_weekly_hours,
    d.scheduled_annual_days AS scheduled_annual_days,
    d.full_time AS full_time
    FROM users u
    LEFT JOIN user_detail d ON d.user_id = u.id
    ORDER BY u.id';
$stmt = $pdo->query($sql);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($users);
