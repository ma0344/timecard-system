-- 9999-12-31（無期限センチネル）を NULL に統一
-- 実施前にバックアップ推奨

UPDATE paid_leaves
SET expire_date = NULL
WHERE expire_date >= '9999-01-01';
