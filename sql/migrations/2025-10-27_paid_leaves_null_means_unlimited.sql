-- Null期限を「無期限」として扱うため、期限デフォルト付与トリガーを廃止
-- 以後、expire_date が NULL の場合はそのまま保存し、アプリ側・再計算で有効付与として集計する

DROP TRIGGER IF EXISTS trg_paid_leaves_before_insert_default_expire;
DROP TRIGGER IF EXISTS trg_paid_leaves_before_update_default_expire;

-- 既存データは変更しません（必要に応じて別途UPDATEを実行してください）
