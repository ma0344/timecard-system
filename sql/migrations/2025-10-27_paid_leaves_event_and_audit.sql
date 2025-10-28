-- Add event_id to paid_leave_logs and event/audit tables

ALTER TABLE paid_leave_logs
  ADD COLUMN event_id INT NULL AFTER paid_leave_id,
  ADD INDEX idx_paid_leave_logs_event_id (event_id);

CREATE TABLE IF NOT EXISTS paid_leave_use_events (
  id INT NOT NULL AUTO_INCREMENT,
  user_id INT NOT NULL,
  used_date DATE NOT NULL,
  total_hours FLOAT NOT NULL,
  reason VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  INDEX idx_plue_user_date (user_id, used_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS audit_logs (
  id BIGINT NOT NULL AUTO_INCREMENT,
  actor_user_id INT NOT NULL,
  target_user_id INT NULL,
  action VARCHAR(100) NOT NULL,
  details JSON NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  INDEX idx_audit_actor_time (actor_user_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
