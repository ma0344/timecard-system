-- leave_requests: ハッシュ列と決裁メタ、監査テーブルの追加（MySQL 8.0 互換: IF NOT EXISTS を使わず実現）

SET @schema := DATABASE();
SET @tbl := 'leave_requests';

-- テーブルが存在する場合のみ続行
SET @tbl_exists := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
                     WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = @tbl);

-- approve_token_hash 列の追加
SET @col_exists := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                     WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = @tbl AND COLUMN_NAME = 'approve_token_hash');
SET @sql := IF(@tbl_exists > 0 AND @col_exists = 0,
    'ALTER TABLE leave_requests ADD COLUMN approve_token_hash CHAR(64) NULL AFTER approve_token',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- decided_ip 列の追加
SET @col_exists := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                     WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = @tbl AND COLUMN_NAME = 'decided_ip');
SET @sql := IF(@tbl_exists > 0 AND @col_exists = 0,
    'ALTER TABLE leave_requests ADD COLUMN decided_ip VARCHAR(45) NULL AFTER decided_at',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- decided_user_agent 列の追加
SET @col_exists := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                     WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = @tbl AND COLUMN_NAME = 'decided_user_agent');
SET @sql := IF(@tbl_exists > 0 AND @col_exists = 0,
    'ALTER TABLE leave_requests ADD COLUMN decided_user_agent VARCHAR(255) NULL AFTER decided_ip',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- インデックス（approve_token_hash）追加
SET @idx_exists := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
                     WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = @tbl AND INDEX_NAME = 'idx_leave_requests_approve_token_hash');
SET @sql := IF(@tbl_exists > 0 AND @idx_exists = 0,
    'ALTER TABLE leave_requests ADD INDEX idx_leave_requests_approve_token_hash (approve_token_hash)',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 既存トークンのハッシュをバックフィル
UPDATE leave_requests
     SET approve_token_hash = SHA2(approve_token, 256)
 WHERE approve_token IS NOT NULL AND approve_token_hash IS NULL;

-- 監査テーブル
CREATE TABLE IF NOT EXISTS leave_request_audit (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    action ENUM('create','open','approve','reject') NOT NULL,
    actor_type ENUM('user','admin','token','system') NOT NULL,
    actor_id INT NULL,
    ip VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_lra_request_id (request_id),
    INDEX idx_lra_action (action),
    INDEX idx_lra_actor_type (actor_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
