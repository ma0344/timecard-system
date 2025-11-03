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

- 現在、承認トークンは平文で `leave_requests.approve_token` に保存されている。
- 漏洩時リスクの低減と改ざん検出のため、ハッシュ保存＋監査ログを実装する。

やること

1. DB 変更（マイグレーション）
   - `leave_requests.approve_token` → `approve_token_hash VARCHAR(191)`（UNIQUE）
   - 新規テーブル `leave_request_audit`（id, request_id, action, actor_user_id NULL, ip, ua, created_at）
   - 既存データ移行（有効なトークンがある場合は移行 or 互換期間を設ける）
2. API 変更
   - 生成時: 生トークンをメール送出のみで保持し、DB には `hash(token)` を保存
   - 照会/決裁時: 入力トークンのハッシュで照合
   - 決裁時: `leave_request_audit` へ記録
3. 互換
   - 互換期間: 旧カラム存在時は優先的に新カラムを使用、なければ旧ロジックにフォールバック

受け入れ基準

- DB 上に平文トークンが残らない
- 決裁成功時に `leave_request_audit` に 1 行作成（action=approve/reject）
- 既存リンク（移行前に発行）も互換期間中は利用可

チェックリスト

- [ ] マイグレーション作成と適用
- [ ] 生成/照会/決裁のハッシュ対応
- [ ] 監査テーブル作成と書き込み
- [ ] 互換経路のテスト（新旧）
- [ ] 回帰テスト（メール送信/承認/却下）
