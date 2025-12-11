<?php
// index.php
session_start();

// ログイン状態判定（例: $_SESSION['user_id'] がセットされているか）
if (isset($_SESSION['user_id'])) {
  // ログイン済みならダッシュボードへリダイレクト
  header('Location: myday_dashboard.html');
  exit;
} else {
  // 未ログインならログイン画面へリダイレクト
  header('Location: login.html');
  exit;
}
