# [NOTIF-001] 決裁結果の申請者通知メール（承認/却下）

Meta

- Milestone: Approval hardening
- Project: v1.1 Approval & Security
- Priority: P0
- Labels: notifications, area/backend, priority/P0
- Dependencies: 既存 SMTP/通知設定、SEC-001 推奨
- Estimate: 0.5〜1 日
- Assignees: （未割当）

背景

- 決裁結果を申請者にも自動通知して、手戻りや確認待ちを減らしたい。

やること

1. decide 後フックで申請者アドレス宛に結果通知（承認/却下）
2. 失敗時は決裁は成功のまま、通知エラーを監査に残す
3. 件名/本文テンプレ（申請者名、日付、時間、理由、結果）

受け入れ基準

- 決裁結果で 1 通送られる
- SMTP 失敗時は監査/レスポンスに記録（UI は将来）

チェックリスト

- [ ] 申請者メールの取得と送信実装
- [ ] 送信失敗時の例外ハンドリング
- [ ] 動作確認（承認/却下）
