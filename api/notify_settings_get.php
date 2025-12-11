<?php
/*
 * 目的: 通知設定を取得します（ユーザー別）。
 * 入力: 権限・対象ユーザー識別
 * 出力: 通知設定（種類、閾値、チャネル 等）
 */
?>
<?php
/*
 * 目的: 通知設定（メール/アプリ内）の取得を行います。
 * 入力: なし（ユーザー別設定）
 * 出力: 設定JSON
 */
?>
<?php
// api/notify_settings_get.php
// 通知設定（宛先/有効フラグ）を取得
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

$default = ['enabled' => true, 'recipients' => ''];
try {
  $pdo->exec('CREATE TABLE IF NOT EXISTS app_settings (`key` VARCHAR(191) PRIMARY KEY, `value` TEXT, `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
  $stmt = $pdo->prepare('SELECT value FROM app_settings WHERE `key` = ? LIMIT 1');
  $stmt->execute(['notify']);
  $val = $stmt->fetchColumn();
  if ($val) {
    $obj = json_decode($val, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($obj)) {
      echo json_encode(array_merge($default, $obj));
      exit;
    }
  }
  echo json_encode($default);
} catch (Throwable $e) {
  echo json_encode($default);
}
