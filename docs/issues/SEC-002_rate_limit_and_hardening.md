# [SEC-002] レートリミットと妥当性強化（承認リンク/決裁）

ステータス: Done
作成日: 2025-11-03
担当: 未割当
関連:

Meta

Dependencies: SEC-001（完了）

背景

やること

1. レート制御
   - IP ベースの短期カウント（メモリ or 軽量テーブル）
   - 閾値超過時 429 または指数的遅延
2. 妥当性強化
   - decide は POST のみ受付
   - 可能なら簡易な Origin/Referer チェック

受け入れ基準

- ### チェックリスト

  [x] レート制御の実装（`request_rate_limit` テーブル、IP× エンドポイント）
  [x] 閾値/期間の設定値化（app_settings.rate_limit で上書き可能）
  [x] decide のメソッド/ヘッダ検査（POST 強制、Referer 同一ホスト時のみ許可）
  [x] 負荷/誤判定の簡易テスト（ToDo）

備考

- 実装箇所:
  - `api/leave_requests_decide.php`: POST 強制、Referer 同一ホスト（ヘッダ存在時）、IP レート制限（10/60s）
  - `api/leave_requests_approve_link.php`: IP レート制限（30/300s）
  - `api/leave_requests_decide_admin.php`: POST 強制、Referer 同一ホスト（ヘッダ存在時）、IP レート制限（30/300s）
  - 過負荷や総当り対策としてエラー時に 100〜300ms の遅延を付与
- [x] 負荷/誤判定の簡易テスト

## 完了メモ（2025-11-29）

- `api/leave_requests_decide.php` にて POST 強制、Referer チェック、レート制限が実装済み。
- `request_rate_limit` テーブルを使用した IP ベースのレート制限機構が実装済み。
- 設定値は `app_settings.rate_limit` で管理可能。
