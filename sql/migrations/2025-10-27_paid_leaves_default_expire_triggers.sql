-- 期限未入力時のデフォルト（4年後の同日の前日）をDB側で自動付与するトリガー
-- MySQL 8.0 系想定

-- 既存トリガーがある場合は削除
DROP TRIGGER IF EXISTS trg_paid_leaves_before_insert_default_expire;
DROP TRIGGER IF EXISTS trg_paid_leaves_before_update_default_expire;

DELIMITER $$

CREATE TRIGGER trg_paid_leaves_before_insert_default_expire
BEFORE INSERT ON paid_leaves
FOR EACH ROW
BEGIN
    -- expire_date が NULL または空文字の場合に、grant_date を基準に 4 年後同日の前日に設定
    IF NEW.expire_date IS NULL OR NEW.expire_date = '0000-00-00' THEN
        SET NEW.expire_date = DATE_SUB(DATE_ADD(NEW.grant_date, INTERVAL 4 YEAR), INTERVAL 0 DAY);
    END IF;
END$$

CREATE TRIGGER trg_paid_leaves_before_update_default_expire
BEFORE UPDATE ON paid_leaves
FOR EACH ROW
BEGIN
    -- 更新時も expire_date を NULL/空にされた場合は再計算
    IF NEW.expire_date IS NULL OR NEW.expire_date = '0000-00-00' THEN
        SET NEW.expire_date = DATE_SUB(DATE_ADD(NEW.grant_date, INTERVAL 4 YEAR), INTERVAL 0 DAY);
    END IF;
END$$

DELIMITER ;

-- 既存データで expire_date が NULL のものを一括補完（必要に応じて実行）
UPDATE paid_leaves
SET expire_date = DATE_SUB(DATE_ADD(grant_date, INTERVAL 4 YEAR), INTERVAL 0 DAY)
WHERE expire_date IS NULL;
