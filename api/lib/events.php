<?php
/*
 * 目的: サーバー側イベント送出の共通ライブラリ（SSE向け）。
 * 入力: enqueue系は user_id, type, payload（連想配列）
 * 出力: DBにイベント登録（events_queue）
 */
require_once __DIR__ . '/../../db_config.php';

function events_init_schema($pdo) {
  $pdo->exec('CREATE TABLE IF NOT EXISTS events_queue (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    payload JSON NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    delivered TINYINT(1) NOT NULL DEFAULT 0,
    INDEX(user_id), INDEX(type), INDEX(delivered)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
}

function events_enqueue($userId, $type, $payload = null) {
  global $pdo;
  if (!$pdo) return false;
  events_init_schema($pdo);
  $stmt = $pdo->prepare('INSERT INTO events_queue (user_id, type, payload) VALUES (?, ?, ?)');
  $json = $payload !== null ? json_encode($payload, JSON_UNESCAPED_UNICODE) : null;
  return $stmt->execute([(int)$userId, (string)$type, $json]);
}

function events_fetch_undelivered($userId, $limit = 50) {
  global $pdo;
  if (!$pdo) return [];
  events_init_schema($pdo);
  $stmt = $pdo->prepare('SELECT id, type, payload, created_at FROM events_queue WHERE user_id = ? AND delivered = 0 ORDER BY id ASC LIMIT ?');
  $stmt->bindValue(1, (int)$userId, PDO::PARAM_INT);
  $stmt->bindValue(2, (int)$limit, PDO::PARAM_INT);
  $stmt->execute();
  return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function events_mark_delivered($ids) {
  global $pdo;
  if (!$pdo || empty($ids)) return false;
  $in = implode(',', array_map('intval', $ids));
  return $pdo->exec("UPDATE events_queue SET delivered = 1 WHERE id IN ($in)") !== false;
}
