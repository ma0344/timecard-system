# timecard-system ロードマップ

このドキュメントは有給・勤怠管理の今後の拡張計画を、開発用バックログとして共有するためのものです。VS Code + GitHub でバージョン管理します。必要に応じて GitHub Issues/Projects に分解して運用してください。

## 目的

- 見逃し防止（期限・打刻・申請）
- 管理業務の省力化（レポート/一括操作）
- 利用者の自己完結（自身の残高・履歴の可視化、申請フロー）

---

## 現在の状態（2025-11-03 時点・更新）

- 完了

  - 管理ダッシュボード（基本）
    - 有休 失効間近（30 日以内）カード（件数＋上位表示、再読み込みボタン）
    - 保留中の申請カード（最大 50 件）
      - 各行クリックで管理者用詳細（`admin_request.html?id=…`）を新規タブで開く（トークン非露出）
      - 各行に「承認」「却下」ボタンを配置し、ID ベースの管理者 API でワンクリック決裁
  - 承認フロー（最小構成／Option A）
    - 申請作成 API: `api/leave_requests_create.php`（ワンタイムトークン生成・72h 有効・通知先にメール送信）
    - 承認フォーム: `approval.html`（トークン検証・承認/却下 UI）
    - 承認リンク照会 API: `api/leave_requests_approve_link.php`
    - 決裁 API（トークン）: `api/leave_requests_decide.php`（承認/却下・単回使用・期限検証）
    - 決裁 API（管理者/ID ベース）: `api/leave_requests_decide_admin.php`
    - 申請詳細（管理者）: `admin_request.html` + `api/leave_requests_get.php`
  - 通知/SMTP
    - 通知設定 API: `api/notify_settings_get.php`, `api/notify_settings_save.php`（enabled, recipients CSV）
    - SMTP 設定・テスト: `api/smtp_settings_get.php`, `api/smtp_settings_save.php`, `api/smtp_test_send.php`（DNS/secure 対応）, `api/smtp_test_send_mail.php`（実送）
  - ユーザー向けエントリ

    - 勤務記録一覧（`attendance_list.html`）に有給申請モーダルを追加（`#paidLeaveBtn`）
    - 打刻画面（`punch.html`）に「有給申請」ボタン＋モーダルを追加

  - セキュリティ/監査（第一段 完了）
    - トークン平文の保存停止（DB は `approve_token_hash` のみ保持、メールにはトークンを含める）
    - 互換照合（平文 or ハッシュ）での承認リンク維持
    - ダッシュボード/管理者決裁は ID ベースに移行（`approve_token` 非露出）
    - 監査ログ（`leave_request_audit`）に open/approve/reject を記録、決裁時の IP/UA 記録、管理者決裁では `approver_user_id` を保存
    - MySQL 8.0 互換マイグレーション（INFORMATION_SCHEMA + PREPARE 方式）

- 進行中/保留（次段）

  - セキュリティ/監査の強化: リクエストレート制御（残件）
  - 決裁結果の申請者通知メール（承認/却下）
  - ダッシュボードの打刻アラート実装
  - CSV/PDF 出力（残高・最短失効・期限間近）
  - 一般ユーザーの有給確認 UI 拡張（履歴・残高・最短失効の自己確認）

- 主な API（実装済み）
  - ダッシュボード: `api/dashboard_summary.php`, `api/leave_requests_pending.php`
  - 申請/承認: `api/leave_requests_create.php`, `api/leave_requests_approve_link.php`, `api/leave_requests_decide.php`, `api/leave_requests_decide_admin.php`, `api/leave_requests_get.php`
  - 通知/SMTP: `api/notify_settings_get.php`, `api/notify_settings_save.php`, `api/smtp_settings_get.php`, `api/smtp_settings_save.php`, `api/smtp_test_send.php`, `api/smtp_test_send_mail.php`

## フェーズ 3（短期・小粒から着手）

1. 管理ダッシュボード（`admin.html`）【基本完了／拡張継続】

- 目的: 期限間近・申請保留・打刻アラートの見える化
- 表示: カード（30 日以内の失効人数、未承認申請、当日の打刻異常）＋テーブル（各一覧）
- API（想定）: GET `/api/dashboard_summary`
- 受け入れ基準: 管理者のみ、手動更新ボタン、各カードから詳細へ遷移
- 概算: 2〜3 日

2. 帳票出力（まずは CSV、PDF は後続）【未着手】

- 対象: 有休残高・最短失効一覧 CSV／期限間近レポート CSV
- PDF: mpdf/TCPDF による 1 種テンプレから開始
- 受け入れ基準: 文字化けなし、ヘッダ行あり、小数 2 桁
- 概算: CSV 1〜2 日、PDF 2〜4 日

3. 一般ユーザーの有給確認（`attendance_list.html` 拡張）【一部着手】

- 内容: 残高・最短失効・取得履歴（最新 50 件）・申請一覧（閲覧）
- 権限: 本人のデータのみ
- 概算: 1〜2 日

4. 期限視認性の強化（既存一覧の拡張）【未着手】

- 7 日以内=赤、8〜30 日=オレンジの段階バッジ
- 「30 日以内のみ」フィルタの追加
- 概算: 0.5〜1 日

5. 安定性（再計算/表示）【未着手】

- 一括再計算の実行ログ表示（成功/失敗/所要時間）
- 残高表示のフォールバック（API 失敗時はサマリ値を表示）
- 概算: 0.5〜1 日

6. 設定画面の再設計（カテゴリ化/タブ/セクション化）【部分対応（通知/SMTP セクション追加済）】

- 目的: SMTP 等の新規項目を追加しても混乱しない情報設計に見直し
- アプローチ: 「基本」「通知（メール/SMTP）」「高度な設定」などに分割。見出し/ボーダー/グリッドで可読性を向上
- 受け入れ基準: スマホでも崩れない・各セクションが独立保存/検証できる・既存設定との互換性維持
- 概算: 1〜2 日

---

## フェーズ 4（中期）

6. 有給の取得申請・承認（最小構成）【最小版完了／残高整合・RateLimit は後続】

- DB: `leave_requests` (id, user_id, used_date, hours, reason, status(pending/approved/rejected), approver_user_id, decided_at, created_at)
- API:
  - POST `/api/leave_requests`
  - GET `/api/leave_requests?status=pending`
  - POST `/api/leave_requests/{id}/approve` | `/reject`
- 振る舞い: 承認時に `paid_leave_use_event` とログを生成、残高整合
- 受け入れ基準: 二重承認防止、重複申請警告、監査ログ
- 概算: 5〜8 日
- 現状: トークン承認〜決裁は実装済み（`approval.html` / `api/leave_requests_*`）。
  - 監査ログ（open/approve/reject）と管理者決裁（approver_user_id 記録）は実装済み
  - 残高整合・重複申請警告・RateLimit は次段で対応

7. 打刻画面の情報再設計（給与算定期間の状況表示）

- 表示: 期内の出勤日数/総労働時間/残り日数、直近の打刻異常
- API: 既存 `timecard_status.php` を異常サマリ返却に拡張
- 概算: 1〜2 日

8. 退勤打刻漏れなどの通知（ログイン後ポップアップ/打刻画面バナー）

- 異常例: 当日退勤未入力、連続 n 日未打刻、過去日の欠落
- API: GET `/api/attendance_alerts?user_id=me`
- 受け入れ基準: 1 日 1 回抑止（ローカル/軽量テーブル）
- 概算: 2〜3 日

9. 管理者へのメール通知（申請承認フォーム付き）【完了（Option A: 宛先手動設定）】

- 目的: 有休申請の見逃し防止と承認作業の省力化（メールからフォームへ直リンク）
- SMTP/設定: 管理画面に SMTP ホスト/ポート/ユーザー/暗号化方式/送信元の設定 UI、テスト送信
- テンプレート: 件名/本文の簡易テンプレ（申請者・日付・時間・理由を差し込み）
- 承認フォーム: メール内 URL で開く軽量ページ（モバイル対応）。ワンタイムトークン/72h 有効/単回使用。承認/却下で `leave_requests` を更新し監査ログを記録
- API（案）:
  - POST `/api/leave_requests/{id}/notify_admins`（管理者全員 or 役割別宛先に通知）
  - GET `/api/leave_requests/approve_link?token=...`（フォーム表示用の署名付きリンク）
  - POST `/api/leave_requests/{id}/approve|reject`（フォーム送信用）
- DB（案）: `mail_queue`（id, to_email, subject, body, status, try_count, last_error, created_at, sent_at, token(optional)）
- セキュリティ: トークンはハッシュ保存/単回/期限切れ。ダッシュボード決裁は ID ベース。監査（open/approve/reject, IP/UA, approver_user_id）対応。
- 受け入れ基準: 申請作成時に管理者へ通知が届く／リンクから承認・却下が完了／監査ログが残る／テスト送信が成功
- 概算: 3〜5 日（設定・テンプレ・リンク承認・監査まで）
- 現状: 通知送信・リンク承認・決裁は完了（`notify_*`, `smtp_*`, `leave_requests_*`）。監査ログ対応済み。

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

## 次に実装する 3 件（候補／更新）

- セキュリティ/監査の強化（トークンのハッシュ保存、決裁者記録、監査ログ、Rate Limit）
- 決裁結果の申請者通知メール（承認/却下）
- CSV エクスポート（残高/最短失効、期限間近）

---

### 運用メモ

- 本ファイル（`docs/ROADMAP.md`）を単一の真実源とし、変更は PR でレビュー
- 必要に応じて GitHub Issues を起票してタスク化（リンクをこのファイルに追記）
- マイルストーン/Projects への割当は担当者が更新
