# timecard-system ロードマップ

このドキュメントは有給・勤怠管理の今後の拡張計画を、開発用バックログとして共有するためのものです。VS Code + GitHub でバージョン管理します。必要に応じて GitHub Issues/Projects に分解して運用してください。

## 目的

- 見逃し防止（期限・打刻・申請）
- 管理業務の省力化（レポート/一括操作）
- 利用者の自己完結（自身の残高・履歴の可視化、申請フロー）

---

## フェーズ 3（短期・小粒から着手）

1. 管理ダッシュボード（`admin.html`）

- 目的: 期限間近・申請保留・打刻アラートの見える化
- 表示: カード（30 日以内の失効人数、未承認申請、当日の打刻異常）＋テーブル（各一覧）
- API（想定）: GET `/api/dashboard_summary`
- 受け入れ基準: 管理者のみ、手動更新ボタン、各カードから詳細へ遷移
- 概算: 2〜3 日

2. 帳票出力（まずは CSV、PDF は後続）

- 対象: 有休残高・最短失効一覧 CSV／期限間近レポート CSV
- PDF: mpdf/TCPDF による 1 種テンプレから開始
- 受け入れ基準: 文字化けなし、ヘッダ行あり、小数 2 桁
- 概算: CSV 1〜2 日、PDF 2〜4 日

3. 一般ユーザーの有給確認（`attendance_list.html` 拡張）

- 内容: 残高・最短失効・取得履歴（最新 50 件）・申請一覧（閲覧）
- 権限: 本人のデータのみ
- 概算: 1〜2 日

4. 期限視認性の強化（既存一覧の拡張）

- 7 日以内=赤、8〜30 日=オレンジの段階バッジ
- 「30 日以内のみ」フィルタの追加
- 概算: 0.5〜1 日

5. 安定性（再計算/表示）

- 一括再計算の実行ログ表示（成功/失敗/所要時間）
- 残高表示のフォールバック（API 失敗時はサマリ値を表示）
- 概算: 0.5〜1 日

6. 設定画面の再設計（カテゴリ化/タブ/セクション化）

- 目的: SMTP 等の新規項目を追加しても混乱しない情報設計に見直し
- アプローチ: 「基本」「通知（メール/SMTP）」「高度な設定」などに分割。見出し/ボーダー/グリッドで可読性を向上
- 受け入れ基準: スマホでも崩れない・各セクションが独立保存/検証できる・既存設定との互換性維持
- 概算: 1〜2 日

---

## フェーズ 4（中期）

6. 有給の取得申請・承認（最小構成）

- DB: `leave_requests` (id, user_id, used_date, hours, reason, status(pending/approved/rejected), approver_user_id, decided_at, created_at)
- API:
  - POST `/api/leave_requests`
  - GET `/api/leave_requests?status=pending`
  - POST `/api/leave_requests/{id}/approve` | `/reject`
- 振る舞い: 承認時に `paid_leave_use_event` とログを生成、残高整合
- 受け入れ基準: 二重承認防止、重複申請警告、監査ログ
- 概算: 5〜8 日

7. 打刻画面の情報再設計（給与算定期間の状況表示）

- 表示: 期内の出勤日数/総労働時間/残り日数、直近の打刻異常
- API: 既存 `timecard_status.php` を異常サマリ返却に拡張
- 概算: 1〜2 日

8. 退勤打刻漏れなどの通知（ログイン後ポップアップ/打刻画面バナー）

- 異常例: 当日退勤未入力、連続 n 日未打刻、過去日の欠落
- API: GET `/api/attendance_alerts?user_id=me`
- 受け入れ基準: 1 日 1 回抑止（ローカル/軽量テーブル）
- 概算: 2〜3 日

9. 管理者へのメール通知（申請承認フォーム付き）

- 目的: 有休申請の見逃し防止と承認作業の省力化（メールからフォームへ直リンク）
- SMTP/設定: 管理画面に SMTP ホスト/ポート/ユーザー/暗号化方式/送信元の設定 UI、テスト送信
- テンプレート: 件名/本文の簡易テンプレ（申請者・日付・時間・理由を差し込み）
- 承認フォーム: メール内 URL で開く軽量ページ（モバイル対応）。ワンタイムトークン/72h 有効/単回使用。承認/却下で `leave_requests` を更新し監査ログを記録
- API（案）:
  - POST `/api/leave_requests/{id}/notify_admins`（管理者全員 or 役割別宛先に通知）
  - GET `/api/leave_requests/approve_link?token=...`（フォーム表示用の署名付きリンク）
  - POST `/api/leave_requests/{id}/approve|reject`（フォーム送信用）
- DB（案）: `mail_queue`（id, to_email, subject, body, status, try_count, last_error, created_at, sent_at, token(optional)）
- セキュリティ: トークンはハッシュ保存/単回/期限切れ、CSRF 対策、承認権限チェック（メールリンク経由でも権限者のみ）
- 受け入れ基準: 申請作成時に管理者へ通知が届く／リンクから承認・却下が完了／監査ログが残る／テスト送信が成功
- 概算: 3〜5 日（設定・テンプレ・リンク承認・監査まで）

---

## 将来（拡張）

- 通知スケジューラ（メール/Slack）と設定 UI
- PDF 帳票の種類追加（勤務表、年間有休台帳など）
- 申請の多段承認/代理申請
- 一覧の仮想スクロール/ページング、サーバサイド検索

---

## DB スキーマ（差分案）

- `leave_requests` 新規
- `mail_queue` 新規（送信キュー。最小は同期送信でも可、拡張で非同期化）
- 将来: `notifications`, `notification_settings`, `schedules`（ジョブ定義）
- 休職対応時: `user_detail` に `on_leave` / `leave_from` / `leave_to` など

---

## API 追加/変更（案）

- ダッシュボード: GET `/api/dashboard_summary`
- 期限レポート: GET `/api/paid_leave_expiring_report?within=30`
- 周年プレビュー/実行: POST `/api/paid_leave_anniv_preview`, POST `/api/paid_leave_anniv_grant`
- 申請ワークフロー: 上記 `leave_requests` 系 API + メール通知 `/api/leave_requests/{id}/notify_admins` と承認リンク `/api/leave_requests/approve_link?token=...`
- 打刻異常: GET `/api/attendance_alerts?user_id=...`

---

## 受け入れ基準（抜粋）

- ダッシュボード: 管理者のみ、手動更新、詳細遷移
- CSV/PDF: 文字化けなし、桁数/フォーマット統一
- 申請: 二重承認防止、残高整合、監査ログ
- 一般ユーザー画面: 権限境界の遵守、モバイル可読性

---

## 概算工数（ラフ）

- ダッシュボード: 2〜3 日
- CSV: 1〜2 日 / PDF: 2〜4 日
- 一般向け有給確認: 1〜2 日
- 申請・承認: 5〜8 日
- 打刻画面再設計: 1〜2 日
- 通知: 2〜3 日

---

## 次に実装する 3 件（候補）

- 期限色分け＋フィルタ（一覧強化）
- CSV エクスポート（残高/最短失効、期限間近）
- 管理ダッシュボードの素の版（カード＋テーブル）

---

### 運用メモ

- 本ファイル（`docs/ROADMAP.md`）を単一の真実源とし、変更は PR でレビュー
- 必要に応じて GitHub Issues を起票してタスク化（リンクをこのファイルに追記）
- マイルストーン/Projects への割当は担当者が更新
