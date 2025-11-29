# [EXP-001] CSV エクスポート（残高/最短失効、期限間近）

ステータス: Done
作成日: 2025-11-03
担当: 未割当
関連: api/paid_leave_expiring_report.php

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

- [x] CSV API 実装
- [x] ダウンロード UI
- [x] 文字コード/改行コードの選定と検証（Excel 互換: Shift-JIS）
- [x] 数値/桁のフォーマット統一（時間は小数 2 桁、列ヘッダ固定）
- [x] 表示確認（Win/Mac）

## 完了メモ（2025-11-29）

- `api/paid_leave_expiring_report.php` 実装完了（Shift-JISエンコーディング）
- `admin.html` の「有休 失効間近（30日以内）」カードにCSVダウンロードボタン追加
- すべての手動テスト（1-5）が成功
- Windows Excelでの文字化けなし確認済み
