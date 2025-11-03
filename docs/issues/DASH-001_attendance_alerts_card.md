# [DASH-001] 打刻アラート（カード実装）

Meta

- Milestone: Usability & alerts
- Project: v1.2 Usability & Reporting
- Priority: P1
- Labels: dashboard, area/backend, area/frontend, priority/P1
- Dependencies: なし
- Estimate: 1〜1.5 日
- Assignees: （未割当）

背景

- 未退勤や欠落を早期に把握し、管理対応を前倒ししたい。

やること

1. API: `api/attendance_alerts.php`（当日未退勤、連続 n 日未打刻、過去日の欠落など）
2. admin.html の「打刻アラート」カードに一覧を表示

受け入れ基準

- 代表的な 2〜3 種のアラートが表示される
- 管理者のみ参照可

チェックリスト

- [ ] API の設計/実装
- [ ] admin.html 反映
- [ ] 軽い負荷テスト（集計の重さ）
