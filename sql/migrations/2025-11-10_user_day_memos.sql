-- user_day_memos: ユーザーごとの当日メモを保存
-- 退勤時にクライアントから送信されたメモを保管

CREATE TABLE IF NOT EXISTS `user_day_memos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `work_date` date NOT NULL,
  `memo_text` text,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_user_date` (`user_id`,`work_date`),
  KEY `idx_user` (`user_id`),
  CONSTRAINT `fk_user_day_memos_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
