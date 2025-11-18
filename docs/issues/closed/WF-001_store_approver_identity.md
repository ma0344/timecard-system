# [WF-001] 決裁者情報の保存（approver_user_id と監査）

ステータス: Closed
作成日: 2025-11-03
担当: 未割当
関連:

Meta

- Milestone: Approval hardening
- Project: v1.1 Approval & Security
- Priority: P0
- Labels: area/backend, workflow, priority/P0
- Dependencies: SEC-001
- Estimate: 0.5 日
- Assignees: （未割当）

背景

- ダッシュボード（管理者ログイン）経由の決裁では approver_user_id を保存しておきたい。
- メールリンク経由（未ログイン）は監査（IP/UA）で追跡可能にする。

やること

1. decide API で、ログイン済み管理者が操作した場合は `approver_user_id` を保存
2. 監査記録へ actor_user_id（NULL 可）、ip、ua を格納

- ### 受け入れ基準

  [x] ダッシュボード決裁で `approver_user_id` が埋まる
  [x] メールリンク決裁で `approver_user_id` は NULL、監査レコードに IP/UA が残る

- ### チェックリスト

  [x] decide のセッション確認と保存処理（`leave_requests_decide_admin.php` で `approver_user_id` を保存）
  [x] 監査項目の実装（SEC-001 のテーブルを利用、IP/UA 記録）
  [x] 回帰（承認/却下/期限切れ/既決済）

備考

- メールリンク経由（未ログイン）の決裁は `approver_user_id` は NULL のまま。actor_type=token として IP/UA を監査に記録。
