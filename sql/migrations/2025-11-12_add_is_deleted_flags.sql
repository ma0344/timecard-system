-- Add logical delete flags to timecards and breaks
ALTER TABLE `timecards`
  ADD COLUMN `is_deleted` TINYINT(1) NOT NULL DEFAULT 0 AFTER `vehicle_distance`,
  ADD COLUMN `deleted_at` DATETIME NULL DEFAULT NULL AFTER `is_deleted`;

ALTER TABLE `breaks`
  ADD COLUMN `is_deleted` TINYINT(1) NOT NULL DEFAULT 0 AFTER `break_end_manual`,
  ADD COLUMN `deleted_at` DATETIME NULL DEFAULT NULL AFTER `is_deleted`;

-- Optional covering index to speed up active queries
CREATE INDEX IF NOT EXISTS `idx_timecards_user_date_active`
  ON `timecards` (`user_id`, `work_date`, `is_deleted`);

CREATE INDEX IF NOT EXISTS `idx_breaks_timecard_active`
  ON `breaks` (`timecard_id`, `is_deleted`);
