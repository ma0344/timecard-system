-- Add paid leave related settings to settings table
-- Server version noted in dump: MySQL 8.0.29 (JSON available)

ALTER TABLE `settings`
  ADD COLUMN `paid_leave_valid_months` int NOT NULL DEFAULT 24 AFTER `legal_hours_31`,
  ADD COLUMN `paid_leave_rules` JSON NULL AFTER `paid_leave_valid_months`;

-- Initialize defaults if the single settings row already exists
UPDATE `settings`
  SET `paid_leave_valid_months` = COALESCE(`paid_leave_valid_months`, 24),
      `paid_leave_rules` = COALESCE(`paid_leave_rules`,
        JSON_OBJECT(
          'milestones', JSON_ARRAY('6m','1y6m','2y6m','3y6m','4y6m','5y6m','6y6m+'),
          'fulltime', JSON_ARRAY(10,11,12,14,16,18,20),
          'parttime', JSON_OBJECT(
            '4d', JSON_ARRAY(7,8,9,10,12,13,15),
            '3d', JSON_ARRAY(5,6,6,8,9,10,11),
            '2d', JSON_ARRAY(3,4,4,5,6,6,7),
            '1d', JSON_ARRAY(1,2,2,2,3,3,3)
          )
        )
      );
