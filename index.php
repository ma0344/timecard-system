<?php
// index.php
session_start();

// ログイン状態判定（例: $_SESSION['user_id'] がセットされているか）
if (isset($_SESSION['user_id'])) {
    // ログイン済みなら打刻画面へリダイレクト
    header('Location: punch.html');
    exit;
} else {
    // 未ログインならログイン画面へリダイレクト
    header('Location: login.html');
    exit;
}
