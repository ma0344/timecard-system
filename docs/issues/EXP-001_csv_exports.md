# [EXP-001] CSV エクスポート（残高/最短失効、期限間近）

Meta

- Milestone: Usability & alerts
- Project: v1.2 Usability & Reporting
- Priority: P1
- Labels: export, area/backend, area/frontend, priority/P1
- Dependencies: dashboard_summary の集計
- Estimate: 1〜1.5 日
- Assignees: （未割当）

背景

- まずは CSV での出力に対応し、レポートのたたきを用意したい。

やること

1. API: `api/paid_leave_expiring_report.php?within=30`（text/csv 応答）
2. admin.html にダウンロードリンク追加
3. 文字エンコーディング/改行コード/日本語エスケープ整備

受け入れ基準

- Excel/スプレッドで崩れない
- 列ヘッダ/桁数が仕様通り

チェックリスト

- [ ] CSV API 実装
- [ ] ダウンロード UI
- [ ] 表示確認（Win/Mac）
