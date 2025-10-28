-- Migration: introduce per-grant consumption and per-user leave summary

-- 1) Add consumed_hours_total to paid_leaves (if not exists)
ALTER TABLE paid_leaves
  ADD COLUMN consumed_hours_total DECIMAL(6,2) NOT NULL DEFAULT 0 AFTER grant_hours;

-- 2) Summary table per user
CREATE TABLE IF NOT EXISTS user_leave_summary (
  user_id INT NOT NULL PRIMARY KEY,
  balance_hours DECIMAL(8,2) NOT NULL DEFAULT 0,
  used_total_hours DECIMAL(8,2) NOT NULL DEFAULT 0,
  next_expire_date DATE NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_user_leave_summary_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 3) Daily expire run guard
CREATE TABLE IF NOT EXISTS leave_expire_runs (
  run_date DATE NOT NULL PRIMARY KEY,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);
