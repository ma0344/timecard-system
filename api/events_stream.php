<?php
/*
 * 目的: ログイン中ユーザー向けのSSEイベントストリームを提供します。
 * 入力: セッション認証、任意で心拍/タイムアウト
 * 出力: text/event-stream でイベント（type/payload）を逐次送出
 */
session_start();
require_once '../db_config.php';
require_once __DIR__ . '/lib/events.php';

// 認証
if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  header('Content-Type: application/json');
  echo json_encode(['error' => 'not logged in']);
  exit;
}
$userId = (int)$_SESSION['user_id'];

// SSEヘッダ
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no');

// シングルショット配信（待機なし）。必要なら query で wait=N を指定して短時間だけ待機。
$wait = isset($_GET['wait']) ? max(0, min(10, (int)$_GET['wait'])) : 0;

// 初期取りこぼし防止: 未配信イベントを即送出
$pending = events_fetch_undelivered($userId, 100);
if (!empty($pending)) {
  $ids = [];
  foreach ($pending as $ev) {
    $payload = $ev['payload'] ? $ev['payload'] : '{}';
    echo "event: {$ev['type']}\n";
    echo "data: {$payload}\n\n";
    $ids[] = (int)$ev['id'];
  }
  @events_mark_delivered($ids);
  @ob_flush();
  @flush();
}

// 短時間のみ待機して、到着分があれば送出（軽量ロングポーリングに近い動作）
if ($wait > 0) {
  $endAt = microtime(true) + $wait;
  while (microtime(true) < $endAt) {
    usleep(200 * 1000); // 200ms
    $pending = events_fetch_undelivered($userId, 50);
    if (!empty($pending)) {
      $ids = [];
      foreach ($pending as $ev) {
        $payload = $ev['payload'] ? $ev['payload'] : '{}';
        echo "event: {$ev['type']}\n";
        echo "data: {$payload}\n\n";
        $ids[] = (int)$ev['id'];
      }
      @events_mark_delivered($ids);
      @ob_flush();
      @flush();
      break; // 一度送出したら終了
    }
  }
}

// 終了（ブラウザは自動再接続）
echo "event: end\n";
echo "data: {}\n\n";
@ob_flush();
@flush();
exit;