# timecard-system ロードマップ

このドキュメントは有給・勤怠管理の今後の拡張計画を、開発用バックログとして共有するためのものです。VS Code + GitHub でバージョン管理します。必要に応じて GitHub Issues/Projects に分解して運用してください。

## 目的
- 見逃し防止（期限・打刻・申請）
- 管理業務の省力化（レポート/一括操作）
- 利用者の自己完結（自身の残高・履歴の可視化、申請フロー）

---

## フェーズ3（短期・小粒から着手）

1) 管理ダッシュボード（`admin.html`）
- 目的: 期限間近・申請保留・打刻アラートの見える化
- 表示: カード（30日以内の失効人数、未承認申請、当日の打刻異常）＋テーブル（各一覧）
- API（想定）: GET `/api/dashboard_summary`
- 受け入れ基準: 管理者のみ、手動更新ボタン、各カードから詳細へ遷移
- 概算: 2〜3日

2) 帳票出力（まずは CSV、PDF は後続）
- 対象: 有休残高・最短失効一覧CSV／期限間近レポートCSV
- PDF: mpdf/TCPDF による1種テンプレから開始
- 受け入れ基準: 文字化けなし、ヘッダ行あり、小数2桁
- 概算: CSV 1〜2日、PDF 2〜4日

3) 一般ユーザーの有給確認（`attendance_list.html` 拡張）
- 内容: 残高・最短失効・取得履歴（最新50件）・申請一覧（閲覧）
- 権限: 本人のデータのみ
- 概算: 1〜2日

4) 期限視認性の強化（既存一覧の拡張）
- 7日以内=赤、8〜30日=オレンジの段階バッジ
- 「30日以内のみ」フィルタの追加
- 概算: 0.5〜1日

5) 安定性（再計算/表示）
- 一括再計算の実行ログ表示（成功/失敗/所要時間）
- 残高表示のフォールバック（API失敗時はサマリ値を表示）
- 概算: 0.5〜1日

---

## フェーズ4（中期）

6) 有給の取得申請・承認（最小構成）
- DB: `leave_requests` (id, user_id, used_date, hours, reason, status(pending/approved/rejected), approver_user_id, decided_at, created_at)
- API: 
  - POST `/api/leave_requests`
  - GET `/api/leave_requests?status=pending`
  - POST `/api/leave_requests/{id}/approve` | `/reject`
- 振る舞い: 承認時に `paid_leave_use_event` とログを生成、残高整合
- 受け入れ基準: 二重承認防止、重複申請警告、監査ログ
- 概算: 5〜8日

7) 打刻画面の情報再設計（給与算定期間の状況表示）
- 表示: 期内の出勤日数/総労働時間/残り日数、直近の打刻異常
- API: 既存 `timecard_status.php` を異常サマリ返却に拡張
- 概算: 1〜2日

8) 退勤打刻漏れなどの通知（ログイン後ポップアップ/打刻画面バナー）
- 異常例: 当日退勤未入力、連続n日未打刻、過去日の欠落
- API: GET `/api/attendance_alerts?user_id=me`
- 受け入れ基準: 1日1回抑止（ローカル/軽量テーブル）
- 概算: 2〜3日

---

## 将来（拡張）
- 通知スケジューラ（メール/Slack）と設定UI
- PDF帳票の種類追加（勤務表、年間有休台帳など）
- 申請の多段承認/代理申請
- 一覧の仮想スクロール/ページング、サーバサイド検索

---

## DBスキーマ（差分案）
- `leave_requests` 新規
- 将来: `notifications`, `notification_settings`, `schedules`（ジョブ定義）
- 休職対応時: `user_detail` に `on_leave` / `leave_from` / `leave_to` など

---

## API 追加/変更（案）
- ダッシュボード: GET `/api/dashboard_summary`
- 期限レポート: GET `/api/paid_leave_expiring_report?within=30`
- 周年プレビュー/実行: POST `/api/paid_leave_anniv_preview`, POST `/api/paid_leave_anniv_grant`
- 申請ワークフロー: 上記 `leave_requests` 系 API
- 打刻異常: GET `/api/attendance_alerts?user_id=...`

---

## 受け入れ基準（抜粋）
- ダッシュボード: 管理者のみ、手動更新、詳細遷移
- CSV/PDF: 文字化けなし、桁数/フォーマット統一
- 申請: 二重承認防止、残高整合、監査ログ
- 一般ユーザー画面: 権限境界の遵守、モバイル可読性

---

## 概算工数（ラフ）
- ダッシュボード: 2〜3日
- CSV: 1〜2日 / PDF: 2〜4日
- 一般向け有給確認: 1〜2日
- 申請・承認: 5〜8日
- 打刻画面再設計: 1〜2日
- 通知: 2〜3日

---

## 次に実装する3件（候補）
- 期限色分け＋フィルタ（一覧強化）
- CSVエクスポート（残高/最短失効、期限間近）
- 管理ダッシュボードの素の版（カード＋テーブル）

---

### 運用メモ
- 本ファイル（`docs/ROADMAP.md`）を単一の真実源とし、変更はPRでレビュー
- 必要に応じて GitHub Issues を起票してタスク化（リンクをこのファイルに追記）
- マイルストーン/Projects への割当は担当者が更新
