-- Migration: day_status_overrides table
-- Creates a table to store per-user per-date manual day status overrides (e.g., off_full/off_am/off_pm/working/ignore)

CREATE TABLE IF NOT EXISTS day_status_overrides (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  date DATE NOT NULL,
  status VARCHAR(16) NOT NULL,
  note VARCHAR(255) DEFAULT NULL,
  created_by INT DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  revoked_at DATETIME DEFAULT NULL,
  KEY idx_user_date (user_id, date),
  KEY idx_active (user_id, date, revoked_at),
  CONSTRAINT fk_dso_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_dso_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
