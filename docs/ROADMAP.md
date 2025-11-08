# timecard-system ロードマップ

このドキュメントは有給・勤怠管理の今後の拡張計画を、開発用バックログとして共有するためのものです。VS Code + GitHub でバージョン管理します。必要に応じて GitHub Issues/Projects に分解して運用してください。

## 目的

- 見逃し防止（期限・打刻・申請）
- 管理業務の省力化（レポート/一括操作）
- 利用者の自己完結（自身の残高・履歴の可視化、申請フロー）

---

## 現在の状態（2025-11-05 時点・更新）

<details><summary style="font-size:140%;"><b>完了</b></summary>

    - 管理ダッシュボード（基本）
    - 有休 失効間近（30 日以内）カード（件数＋上位表示、再読み込みボタン）
    - 保留中の申請カード（最大 50 件）
        - 各行クリックで管理者用詳細（`admin_request.html?id=…`）を新規タブで開く（トークン非露出）
        - 各行に「承認」「却下」ボタンを配置し、ID ベースの管理者 API でワンクリック決裁
    - 管理ダッシュボード（拡張・実装済み）
    - 打刻アラートカード
        - 種別トグル（未退勤=ON/未打刻=ON/勤務中=OFF、localStorage 永続化）
        - 期間セレクタ（3/5/7 日、localStorage 永続化）
        - 上書き（全休/午前/午後/無視）日は除外してから集計
        - 未打刻は「過去日のみ」を対象（当日は含めない）
        - ちらつき抑制のため、カード単独再描画（他カードは再ロードしない）
        - UI: `.card-header` 内でタイトルと metric を分離して横並び
        - 未打刻通知モーダルを分離し、複数日まとめて通知＋任意コメント（最大 500 文字）に対応
    - 今日の勤務（一覧）テーブル
        - 全ユーザー（visible=1）を一覧表示、行内で上書き操作（全休/午前/午後/無視/取消）とメモ入力
        - 実績バッジ（未打刻/勤務中/休憩中/退勤済み）を表示
        - 手動更新＋ 60 秒自動更新、フィルタ/ソート対応
        - ボタンの active 状態で上書きの現在値を可視化
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
    - コメント入力欄に残り文字数カウンタ（UX 向上）
    - ユーザー向けエントリ

    - 勤務記録一覧（`attendance_list.html`）に有給申請モーダルを追加（`#paidLeaveBtn`）
    - 打刻画面（`punch.html`）に「有給申請」ボタン＋モーダルを追加

    - セキュリティ/監査（第一段 完了）
    - トークン平文の保存停止（DB は `approve_token_hash` のみ保持、メールにはトークンを含める）
    - 互換照合（平文 or ハッシュ）での承認リンク維持
    - ダッシュボード/管理者決裁は ID ベースに移行（`approve_token` 非露出）
    - 監査ログ（`leave_request_audit`）に open/approve/reject を記録、決裁時の IP/UA 記録、管理者決裁では `approver_user_id` を保存
    - MySQL 8.0 互換マイグレーション（INFORMATION_SCHEMA + PREPARE 方式）
    - レート制限（SEC-002）
    - 各 API に IP× エンドポイントのレート制限を適用（POST 強制・Referer 緩和チェックを含む）
    - 設定は `app_settings.rate_limit` に保存し、管理画面から編集可能（承認リンク表示/決裁（トークン）/決裁（管理者））
    - アプリ内通知（最小）
    - 決裁時に申請者へ通知レコード生成、ユーザー画面でベル＋モーダル表示
    - 未読 0 件のときは通知ベルを非表示にする挙動に変更
    - 通知の個別既読に対応（モーダル内で各通知に「既読」ボタン、実行後に未読バッジを即時更新）

</details>

---

<details open><summary style="font-size:140%;"><b>進行中/保留（次段）</b></summary>

#### 決裁結果の申請者通知メール（承認/却下）【社内ポリシー決定まで保留】

- メールアドレスの取り扱いポリシー確定後に着手

#### ダッシュボードの打刻アラート実装（残タスク）

- 現状: トグル/期間セレクタ/上書き日除外/未打刻は過去日のみ/カード単独再描画/未打刻通知（複数日＋コメント）まで実装済み。
- 残り:
  - 「未打刻」の最終判定ルール（休日/公休/休暇/勤務不要日の扱い）確定
  - サーバー側フィルタへ移行（days/トグルをクエリ化）
  - パフォーマンス検討。

#### 勤怠記録に「休みの明示」記録を追加（全休/午前休/午後休/無視/勤務）

- 管理ダッシュボード「今日の勤務（一覧）」で上書きを登録・取消できるテーブル/API を実装済み。
- 今後: 勤怠画面・集計・アラート全体での連動を拡充し、説明ツールチップ/ヘルプを整備。

######

#### CSV/PDF 出力（残高・最短失効・期限間近）

#### 一般ユーザー向け UI/機能の拡張（概要）

- 詳細な計画は「一般ユーザー向け UI/機能 ロードマップ」へ分離しました。
- 参照: `docs/ROADMAP_USER_UI.md`

## 着手順（メール保留を考慮）

1. ダッシュボードの打刻アラート実装（最終ルール確定と性能検証）
2. CSV/PDF 出力
3. 一般ユーザーの有給確認 UI 拡張
</details>

## 主な API（実装済み）

- ダッシュボード: `api/dashboard_summary.php`, `api/leave_requests_pending.php`
- 打刻アラート: `api/attendance_alerts.php`
- 申請/承認: `api/leave_requests_create.php`, `api/leave_requests_approve_link.php`, `api/leave_requests_decide.php`, `api/leave_requests_decide_admin.php`, `api/leave_requests_get.php`
- 通知/SMTP: `api/notify_settings_get.php`, `api/notify_settings_save.php`, `api/smtp_settings_get.php`, `api/smtp_settings_save.php`, `api/smtp_test_send.php`, `api/smtp_test_send_mail.php`
- 当日実績サマリ: `api/today_status_summary.php`
- 日上書き状態: `api/day_status_get.php`, `api/day_status_set.php`, `api/day_status_clear.php`

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

3. 一般ユーザー向け UI/機能（概要・詳細は別紙）【一部着手】

- 要点: My Day（個人ダッシュボード）、勤怠修正申請、月次カレンダー、通知センター強化 等
- 権限: 本人のデータのみ
- 概算: 各項目は `docs/ROADMAP_USER_UI.md` を参照

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

7. 有給の取得申請・承認（最小構成）【最小版完了／残高整合・RateLimit は後続】

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

8. 打刻画面の情報再設計（概要）

- 表示: 期内サマリ等。詳細は `docs/ROADMAP_USER_UI.md` を参照

9. 退勤打刻漏れなどの通知（概要）

- 異常例と抑止要件等の詳細は `docs/ROADMAP_USER_UI.md` へ委譲

10. 管理者へのメール通知（申請承認フォーム付き）【完了（Option A: 宛先手動設定）】

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
- 予定表/シフト導入（所定休日・シフト・祝日カレンダー）
  - 目的: 「未打刻」の誤検知を低減し、勤務不要日/公休/休暇の区別を厳密化。
  - 連動: アラート・ダッシュボード・勤怠入力・集計に反映。
- PDF 帳票の種類追加（勤務表、年間有休台帳など）
- 申請の多段承認/代理申請
- 一覧の仮想スクロール/ページング、サーバサイド検索
- セキュリティ/監査の強化（後続）
  - 初期対応: 監査ビュー/CSV エクスポート（`admin_audit.html` + `api/audit_list.php`）、RateLimit スナップショット可視化（`api/rate_limit_stats.php`）を拡張
  - 後続: 監査の検索条件拡充・ページング、RateLimit の履歴集計/可視化（必要なら別テーブル化）
- 通知モーダルの送信完了時に「対象ユーザーの未読通知数」をフロント側でも更新（視覚的なフィードバック強化）

### 将来（低優先の提案・バックログ）

- 失効カードの情報拡張（低優先）

  - 目的: 「有休 失効間近」カードに、各失効日の「失効予定時間（その日に失効する合計時間）」を表示して意思決定を支援する。
  - 表示例: `2025-12-31 5.0h 失効`（行末にバッジ/注記として併記）
  - 受け入れ基準:
    - カード上位一覧の各行に、当該失効日の失効予定時間（h）を表示（小数 1–2 桁、丸めは現在の残高表示に準拠）
    - 件数表示との不整合がない（0h の場合は表示省略可）
    - アクセシビリティ: ツールチップ/タイトル属性で補助テキスト
  - API/実装メモ:
    - `api/dashboard_summary.php` の expiring セクションに `expiring_hours`（その日付で失効する時間合計）を追加、もしくは `api/paid_leave_expiring_report` を拡張
    - サマリ高速化のため、既存の残高・失効計算ロジックを再利用（N 件上位のみ計算）

- アプリ内通知 UX の強化

  - 未読への戻し（再未読）、バッチ既読の改良
  - Web Push（PWA）対応、通知音/サイレント時間帯の設定
  - 通知本文からの文脈遷移（対象レコードへジャンプ）

- 勤怠表（admin_attendance）の有給表示（低優先）

  - 目的: 管理画面の勤怠テーブル（attendance-table）に「有給取得のイベント（時間・理由）」も同じ一覧上で視認できるようにし、月度内の勤務と有給の全体像を 1 画面で把握できるようにする。
  - 表示案:
    - 勤務レコード行の間に「有給」行を挿入（種別=取得、時間、理由）。スタイルはバッジ＋淡色背景で勤務行と区別。
    - 当日が勤務＋有給（時間単位）混在の場合は、勤務行の下にサブ行（インデント）で有給を表示。
  - 受け入れ基準:
    - 表示期間（periodStart/periodEnd による月度）で `paid_leave_use_event` を取得し、日付一致でテーブルへマージ表示。
    - 絞り込み/ソートは従来のまま（まずは表示のみ）。
    - アクセシビリティ: 種別・時間・理由がテキストでも判読可能。
  - API/実装メモ:
    - 既存 `api/paid_leave_history.php` の use_events を流用（期間フィルタ追加）または `api/paid_leave_use_event_list` を新設。
    - クライアント側で attendance と use_events を日付でマージし、行テンプレートを出し分け。
    - スタイルは `css/common.css` のバッジ定義を再利用（info/warn/critical など）。

- 外部通知連携
  - Slack/LINE Webhook への任意通知（結果通知・ダイジェスト）
  - 管理用チャンネルへの集約通知
- モバイル UI の操作整理（ハンバーガーメニュー導入）
  - 対象: `punch.html`, `attendance_list.html`
  - 目的: 使用頻度の低いボタン/リンクをメニューへ集約し、主要アクション（打刻・保存等）を主ボタンに限定して誤操作を低減
  - 受け入れ基準: スマホ片手操作で開閉しやすい・アクセシビリティ（キーボード/フォーカス可視化）・既存導線の維持（代替位置が明示）
  - 概算: 0.5〜1.5 日

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

- ダッシュボードの打刻アラート最終ルール確定（休日/公休/勤務不要日の扱いと性能検証）
- 一般ユーザー向け UI/機能の拡張（詳細は `docs/ROADMAP_USER_UI.md`）
- CSV エクスポート（残高/最短失効、期限間近）

---

## 小改善タスク（短期）

- 休日/公休日付のサーバー側統一ロジック化（未打刻判定からの除外処理）
- 打刻アラートの SQL 最適化と必要 INDEX の再点検（期間/種別トグルを考慮）
- アクセシビリティ: モーダル/FW ボタンのフォーカス可視化、キーボード操作の確認
- 空状態/エラー時 UI の明示（カード/テーブルのメッセージ表現統一）

---

### 運用メモ

- 本ファイル（`docs/ROADMAP.md`）を単一の真実源とし、変更は PR でレビュー
- 必要に応じて GitHub Issues を起票してタスク化（リンクをこのファイルに追記）
- マイルストーン/Projects への割当は担当者が更新

---

## Copilot とまだ共有していない追加したい機能

- ダッシュボード
  - 打刻アラートのアイテムを展開式にし、対象の複数日から特定の日付を選択処理できるようにする
  - ダッシュボードのカードを折りたためるようにする
  - 勤務一覧を折りたためるようにする
  - ボタンエリアを折りたためるようにする
