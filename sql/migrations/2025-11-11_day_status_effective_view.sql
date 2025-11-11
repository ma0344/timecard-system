-- Create an effective per-day status view that prioritizes overrides over baseline
-- This view normalizes override codes (off_full/off_am/off_pm/ignore) to day_status enum values (off/am_off/pm_off/ignore)

DROP VIEW IF EXISTS day_status_effective;
CREATE VIEW day_status_effective AS
-- Rows where baseline exists (with possible override applied)
SELECT 
  COALESCE(o.user_id, ds.user_id) AS user_id,
  COALESCE(o.date, ds.date)       AS date,
  COALESCE(
    CASE o.status 
      WHEN 'off_full' THEN 'off'
      WHEN 'off_am'   THEN 'am_off'
      WHEN 'off_pm'   THEN 'pm_off'
      WHEN 'ignore'   THEN 'ignore'
      ELSE NULL
    END,
    ds.status,
    'work' -- default when neither exists (rarely reached in this branch)
  ) AS status,
  CASE WHEN o.user_id IS NOT NULL THEN 'override' ELSE 'baseline' END AS source,
  COALESCE(o.note, ds.note) AS note
FROM day_status ds
LEFT JOIN (
  SELECT 
    user_id,
    date,
    SUBSTRING_INDEX(GROUP_CONCAT(status ORDER BY id DESC), ',', 1) AS status,
    SUBSTRING_INDEX(GROUP_CONCAT(COALESCE(note, '') ORDER BY id DESC), ',', 1) AS note
  FROM day_status_overrides
  WHERE revoked_at IS NULL
  GROUP BY user_id, date
) o ON o.user_id = ds.user_id AND o.date = ds.date

UNION

-- Rows where override exists even if baseline does not
SELECT 
  o.user_id,
  o.date,
  CASE o.status 
    WHEN 'off_full' THEN 'off'
    WHEN 'off_am'   THEN 'am_off'
    WHEN 'off_pm'   THEN 'pm_off'
    WHEN 'ignore'   THEN 'ignore'
    ELSE 'work'
  END AS status,
  'override' AS source,
  o.note
FROM (
  SELECT 
    user_id,
    date,
    SUBSTRING_INDEX(GROUP_CONCAT(status ORDER BY id DESC), ',', 1) AS status,
    SUBSTRING_INDEX(GROUP_CONCAT(COALESCE(note, '') ORDER BY id DESC), ',', 1) AS note
  FROM day_status_overrides
  WHERE revoked_at IS NULL
  GROUP BY user_id, date
) o
LEFT JOIN day_status ds ON ds.user_id = o.user_id AND ds.date = o.date
WHERE ds.user_id IS NULL;