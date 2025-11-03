# [USER-UI-001] 一般ユーザーの有給確認 UI 拡張

Meta

- Milestone: Usability & alerts
- Project: v1.2 Usability & Reporting
- Priority: P1
- Labels: area/frontend, ux, priority/P1
- Dependencies: 既存 API の拡張
- Estimate: 1.5〜2.5 日
- Assignees: （未割当）

背景

- 自分の残高/最短失効/履歴/申請状況を一画面で把握できるようにする。

やること

1. attendance_list.html に「有給」タブ or セクションを追加
2. 残高・最短失効・直近履歴・申請一覧（保留/決裁済）
3. 必要に応じて軽微な API 拡張

受け入れ基準

- 本人のデータのみ表示
- モバイル表示で可読性が保たれる

チェックリスト

- [ ] UI 実装
- [ ] API 連携
- [ ] 表示確認（スマホ幅）
