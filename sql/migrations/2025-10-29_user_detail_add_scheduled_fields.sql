-- Add scheduled work pattern fields for rigorous classification (Stage B)
ALTER TABLE user_detail
  ADD COLUMN scheduled_weekly_days TINYINT NULL AFTER contract_hours_per_day,
  ADD COLUMN scheduled_weekly_hours DECIMAL(5,2) NULL AFTER scheduled_weekly_days,
  ADD COLUMN scheduled_annual_days SMALLINT NULL AFTER scheduled_weekly_hours;

-- Optional: indexes for querying by user_id remain as-is; no new index necessary.
