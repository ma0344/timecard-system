-- Migration: attendance_period_locks
-- Purpose: Track locked (confirmed) attendance periods per user or globally (user_id NULL).
-- Allows reopen by updating status to 'reopened' (history kept).

CREATE TABLE IF NOT EXISTS attendance_period_locks (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  status ENUM('locked','reopened') NOT NULL DEFAULT 'locked',
  locked_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  locked_by INT NOT NULL,
  reopened_at DATETIME NULL,
  reopened_by INT NULL,
  note TEXT NULL,
  version INT NOT NULL DEFAULT 1,
  CONSTRAINT chk_apl_dates CHECK (start_date <= end_date),
  INDEX idx_apl_user_dates (user_id, start_date, end_date),
  INDEX idx_apl_status (status)
);

-- Optional FK references (commented out if users table constraints not yet defined):
-- ALTER TABLE attendance_period_locks ADD CONSTRAINT fk_apl_user FOREIGN KEY (user_id) REFERENCES users(id);
-- ALTER TABLE attendance_period_locks ADD CONSTRAINT fk_apl_locked_by FOREIGN KEY (locked_by) REFERENCES users(id);
-- ALTER TABLE attendance_period_locks ADD CONSTRAINT fk_apl_reopened_by FOREIGN KEY (reopened_by) REFERENCES users(id);
