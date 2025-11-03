# [SEC-001] トークンのハッシュ保存＋監査ログ

Meta

- Milestone: Approval hardening
- Project: v1.1 Approval & Security
- Priority: P0
- Labels: area/backend, area/db, security, priority/P0
- Dependencies: なし（現行承認フローは完成済み）
- Estimate: 1.5〜2 日
- Assignees: （未割当）

背景

- これまで承認トークンは平文で `leave_requests.approve_token` に保存されていた。
- 漏洩時リスクの低減のため、ハッシュ保存＋監査ログを実装する。

やること

1. DB 変更（マイグレーション）
   - `leave_requests` に `approve_token_hash CHAR(64)` を追加（インデックス付与）
   - 決裁メタ: `decided_ip`, `decided_user_agent` を追加
   - 新規テーブル `leave_request_audit`（id, request_id, action(create/open/approve/reject), actor_type(user/admin/token/system), actor_id NULL, ip, user_agent, created_at）
   - 既存データ移行: 旧 `approve_token` から `approve_token_hash` をバックフィル
2. API 変更
   - 生成時: 生トークンはメール送出のみ。DB には `approve_token_hash` のみ保存（平文は NULL）
   - 照会/決裁時: 入力トークンのハッシュで照合（互換として平文列が存在すれば OR 条件で許容）
   - 監査: open/approve/reject を `leave_request_audit` に記録
3. 互換
   - 旧リンクも有効（平文/ハッシュの両対応照合）。段階的に平文列の撤廃を進める。

受け入れ基準

- DB 上に平文トークンが残らない
- 決裁成功時に `leave_request_audit` に 1 行作成（action=approve/reject）
- 既存リンク（移行前に発行）も互換期間中は利用可

チェックリスト

- [x] マイグレーション作成と適用（MySQL 8.0 互換の条件付き ALTER）
- [x] 生成/照会/決裁のハッシュ対応（create/get/decide）
- [x] 監査テーブル作成と書き込み（open/approve/reject, IP/UA, actor_type）
- [x] 互換経路のテスト（旧リンク/新実装）
- [x] 回帰テスト（メール送信/承認/却下）

備考

- ダッシュボード決裁は ID ベースの管理者 API に移行（`api/leave_requests_decide_admin.php`）。`approver_user_id` を保存。
- `api/leave_requests_pending.php` はトークンを返却しないよう変更。UI 行クリックは `admin_request.html?id=...` に遷移。
