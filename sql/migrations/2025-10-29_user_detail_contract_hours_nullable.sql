-- Make contract_hours_per_day nullable to represent 'no contract hours set'
START TRANSACTION;
ALTER TABLE user_detail
  MODIFY COLUMN contract_hours_per_day FLOAT NULL DEFAULT NULL;
COMMIT;