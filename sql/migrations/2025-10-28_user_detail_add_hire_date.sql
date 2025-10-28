-- Migration: add hire_date to user_detail
-- Created: 2025-10-28

ALTER TABLE user_detail
  ADD COLUMN hire_date DATE NULL AFTER contract_hours_per_day;

-- Optional: you can set default values or backfill here if needed
-- UPDATE user_detail SET hire_date = NULL WHERE hire_date IS NULL;
