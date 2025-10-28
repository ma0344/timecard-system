-- Migration: add retire_date to user_detail
-- Created: 2025-10-28

ALTER TABLE user_detail
  ADD COLUMN retire_date DATE NULL AFTER hire_date;
