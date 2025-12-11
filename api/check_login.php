<?php
/*
 * 目的: 現在のログイン状態とユーザー情報（権限等）を返します。
 * 入力: なし（セッション）
 * 出力: loggedIn, user_id, is_admin など
 */
?>
<?php
// api/check_login.php
session_start();
header('Content-Type: application/json');
require_once '../db_config.php';

if (isset($_SESSION['user_id'])) {
  $userId = $_SESSION['user_id'];
  // ユーザー名と権限、詳細情報を取得（full_time を含める）。user_detail が未作成の場合もあるため COALESCE を活用
  $stmt = $pdo->prepare('SELECT u.name, u.role, u.visible, COALESCE(d.use_vehicle, 1) AS use_vehicle, COALESCE(d.full_time, 1) AS full_time FROM users u LEFT JOIN user_detail d ON u.id = d.user_id WHERE u.id = ?');
  $stmt->execute([$userId]);
  $user = $stmt->fetch(PDO::FETCH_ASSOC);
  echo json_encode([
    'loggedIn' => true,
    'user_id' => $userId,
    'user_name' => $user ? $user['name'] : '',
    'role' => $user ? $user['role'] : null,
    'is_admin' => ($user && isset($user['role']) && $user['role'] === 'admin'),
    'use_vehicle' => $user && isset($user['use_vehicle']) ? (int)$user['use_vehicle'] : 1,
    'visible' => $user && isset($user['visible']) ? (int)$user['visible'] : 1,
    'full_time' => $user && isset($user['full_time']) ? (int)$user['full_time'] : 1
  ]);
} else {
  echo json_encode([
    'loggedIn' => false
  ]);
}
