<?php
/*
 * 目的: ユーザーログアウトを処理します（セッション終了）。
 * 入力: なし（セッション情報）
 * 出力: 成功/失敗（状態メッセージ）
 */
?>
<?php
/*
 * 目的: ログアウト処理を行います。
 * 入力: なし（セッション）
 * 出力: セッション終了
 */
?>
<?php
// api/logout.php
session_start();
session_unset();
session_destroy();
header('Content-Type: application/json');
echo json_encode(['success' => true]);
