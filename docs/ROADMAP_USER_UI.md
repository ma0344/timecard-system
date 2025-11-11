# 一般ユーザー向け UI/機能 ロードマップ

本ドキュメントは、一般ユーザー（従業員）向けの体験を高めるための UI/機能拡充をまとめた専用ロードマップです。全体版（`docs/ROADMAP.md`）から詳細を分離し、実装に直結するレベルまで掘り下げて記述します。

---

## 目的と効果

- 自己完結を促進し、管理者の手作業（上書き・差戻し）を削減
- 未打刻/退勤漏れの早期気づきと即時対処の促進
- 月次の状況把握（勤務/休憩/超過/休暇）を素早く・正確に

### KPI（例）

- 未打刻の平均遅延日数の減少
- 管理者による日単位の上書き件数の減少
- 1 ユーザーあたりの「月次閲覧 → 修正完了」までの時間短縮

---

## 段階的な計画（優先度順）

### A. My Day（個人ダッシュボード）

- 価値: 打刻画面と一覧の間をつなぐ「今日の状況・アクション」起点を 1 画面に集約
- 内容（MVP）
  - 今日の打刻ステータス（出勤/休憩/退勤）と当日の警告（退勤漏れなど）
  - 直近 3〜5 日の欠落サマリ（各行から対象日へジャンプ）
  - クイックアクション: 打刻画面へ / 欠落日の修正申請へ / 本日のメモ（任意）
  - 未読通知の件数と最新 3 件（通知センターへ遷移）
- 受け入れ基準
  - モバイル片手操作可、初回表示 1 秒以内の体感（スケルトン可）
  - クリック 2 回以内で「打刻」「欠落日ジャンプ」が可能
- API 案
  - GET `api/me_summary.php`：today_status + recent_missing + unread_count をまとめて返す

#### 【具体的な UI/UX 要件・方針】（2025/11 追記）

- ステータス（出勤済・休憩中・退勤済・未打刻など）は色分け＋アイコンで一目で判別（例：出勤済=青、休憩中=黄、退勤済=グレー、未打刻=赤）
- 警告は赤やオレンジなど注意色で背景・バッジ表示し、文字も併記
- 画面上部に大きなステータスサークルやバッジ（色＋アイコン＋短いラベル）
- クイックアクションは親指で押しやすい下部固定ボタンや大きめのタッチ領域
- 44px 以上のタップ領域、片手操作・親指リーチを意識した配置
- 色覚多様性にも配慮し、色＋アイコン＋ラベルの三重表現
- 状態や警告の変化時にアニメーションや色の変化で即時フィードバック
- 未読通知はバッジやドットで強調

- 状態に応じて主要ボタンのみ表示（例：出勤前 → 出勤ボタンのみ、勤務中 → 休憩開始・退勤ボタンのみ、休憩中 → 休憩終了・退勤ボタンのみ）
- 状態に合わないアクションは非表示（グレーアウトではなく非表示）
- 日常的に使うボタンは 1 タップでアクセス、休日・有休などは「その他」や 2 タップ目以降のサブメニューに格納
- 画面下部に大きな 1〜2 個の主要ボタン＋「その他」ボタン、状態遷移ごとに内容が切り替わる

### B. 勤怠修正申請（出退勤・休憩の追加/修正）

- 価値: 管理者上書きの代替。整合性・監査性を高める
- UI
  - `attendance_list.html` の各日から「修正申請」を起動（当日以外）
  - 入力: 日付、出勤/退勤、休憩 0..n、理由、補足メモ
- フロー（推奨: 承認後に確定）
  - 本人申請 → 管理者承認/却下 → 実績へ反映（監査ログ）
- 受け入れ基準
  - UI/サーバーでの時系列・重複/交差チェック
  - 二重申請防止、未来日不可、RateLimit、権限境界の遵守
- API/DB（案）
  - DB: `attendance_corrections` (id, user_id, date, clock_in, clock_out, breaks(json), reason, status(pending/approved/rejected), approver_user_id, decided_at, created_at)
  - POST `api/attendance_corrections_create.php`
  - GET `api/attendance_corrections_list.php?status=pending|mine`
  - POST `api/attendance_corrections_decide_admin.php`（承認/却下）

### C. 月次カレンダー表示と自己サマリ

- 価値: 日リストに加え、全体像（欠落・休暇・超過）を直感で把握
- 内容
  - カレンダーにステータスバッジ（未打刻/勤務/休憩/有給）
  - 月次合計（勤務時間/超過/出勤日数）
  - 欠落セルから「修正申請」直接起動
- パフォーマンス
  - 期間 API のクエリ最適化・INDEX 点検（フェッチ回数/サイズを抑制）

### D. 通知センター強化（ユーザー）

- 価値: 見逃し防止と行動誘導
- 内容
  - 種別フィルタ（勤怠/有給/システム）
  - 本文から文脈遷移（対象画面/対象日へ）
  - 一括既読/未読戻し
  - 将来: PWA/Web Push、バナー/ポップアップ

### E. モバイル操作性・アクセシビリティ（継続）

- クイックウィン
  - フォーカス可視化、44px 以上のタップ領域、低回線でのスケルトン
  - 入力リアルタイム検証、エラーメッセージの一貫性
- 端末差異
  - iOS/Android の時刻入力 UI 差異への配慮

---

## スプリント案（2〜3 週）

- 週 1: My Day（MVP）＋ 打刻画面の前日退勤漏れバナー（該当日へ/修正申請へ導線）
- 週 2: 勤怠修正申請（暫定版 → 承認確定フロー）＋ 通知センターの行動導線強化
- 週 3: 月次カレンダー（簡易版）＋ A11y/モバイル微調整

---

## 受け入れ基準（抜粋）

- 本人のみアクセス可能なデータ境界（権限チェック）
- UI/サーバー双方でのバリデーション（時系列/重複/交差/未来日）
- 主要導線が 2 クリック以内（My Day 起点）
- モバイル最適化（視認性/タップ領域/フォーカス）

---

## API 追加/変更（案）

- GET `api/me_summary.php`（today_status, recent_missing, unread）
- POST `api/attendance_corrections_create.php`
- GET `api/attendance_corrections_list.php`
- POST `api/attendance_corrections_decide_admin.php`
- 既存流用：`api/notifications_get.php`, `api/notifications_read.php`

### セキュリティ・安定性

- IP× エンドポイントの RateLimit（既存方式踏襲）
- Referer 緩和チェック（既存方式踏襲）
- 幂等性（連打抑止/二重送信防止）

---

## DB スキーマ（案）

```
attendance_corrections (
  id BIGINT PK AUTO,
  user_id INT NOT NULL,
  date DATE NOT NULL,
  clock_in TIME NULL,
  clock_out TIME NULL,
  breaks JSON NULL,
  reason VARCHAR(255) NULL,
  status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  approver_user_id INT NULL,
  decided_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX(user_id), INDEX(date), INDEX(status)
)
```

---

## エッジケースと対策

- 休憩の重複・境界（始業前/終業後）→ UI/サーバーで二重チェック
- 勤務不要日（休日/公休日/全休上書き日）への申請 → 事前警告・理由必須化
- 端末時刻のズレ → サーバー時刻で評価、UI は目安表示
- 未来日/重複申請 → 400/409 の明確な応答とガイダンス

---

## 小粒改善（短期）

- punch: 前日退勤漏れ時のバナー＋該当日ジャンプ/申請
- attendance_list: 欠落行の「修正申請」ショートカット
- 通知: 本文 URL のハイライト（安全に）/「対象を開く」ボタン明示

---

## 依存/連携

- `settings_get.php` の period/rounding 設定をサマリ計算へ統一適用
- 既存通知テーブル・API の再利用（本文はプレーン → 表示側で \n を `<br>`）

---

## 改善アイデア（バックログ追加 2025-11-10）

My Day の MVP 完了後に検討する任意改善。優先度は低〜中で都度見直し。

### My Day 関連

- 通知プレビューの強化: 未読のみトグル、種別アイコン（勤怠/有給/システム）、スケルトンローディング
- 通知プレビュー項目クリック時、モーダル内の該当通知へスクロール＆強調（背景/アウトライン）
- 集約 API `api/me_summary.php` を追加して初期ロードの同時取得（status/alerts/unread/latest3）
- 競合回避: 自動更新時の古い fetch を `AbortController` で中断
- タイムライン項目に種別アイコン（出勤/休憩開始/休憩終了/退勤）と軽微アニメーション
- 日付跨ぎ直後のリフレッシュ間隔を一時的に短縮（0:00±1 分）
- アラートピルの種類別アイコン/色覚バリアフリー配色（未退勤=赤丸, 未打刻=橙三角）
- オフライン配慮（SW キャッシュ＋メモの送信キュー化）
- 今日のメモの履歴参照 API `api/day_memo_get.php?date=YYYY-MM-DD` と簡易ビュー
- メモステータスのアイコン化（保存中=⟳/保存済=✓/失敗=⚠）＋ ARIA ライブ領域の最適化
- アクセシビリティ: スキップリンク（#main/#actions/#alerts）、landmark 適正化
- レスポンシブ: 低高さ画面でアクションボタンを 2 列に自動配置

### 共通/横断

- API レイテンシの簡易計測と console ログ（開発時）
- 低回線時は 0.3s 超でスケルトン表示へフォールバック
- 失敗時の指数バックオフ（1.5s→3s、最大 2 回）
- 自動更新間隔を設定化（30/60/120 秒）
- PWA 化（manifest + service worker）／オフライン時はメモのみ編集可

### 将来（低優先）

- 通知の再未読化と一括既読の取り消し
- Web Push / サイレント時間帯設定
- タイムラインのページング（イベントが多い日の負荷軽減）
- 高コントラストモード/フォントスケールのユーザー設定
- メモの差分同期（複数タブ間でのマージ）

## 運用メモ

- 本ファイルを一般ユーザー向けの単一の真実源とし、詳細はここで管理
- 全体版ロードマップ（`docs/ROADMAP.md`）には、要点・優先順位・リンクのみを記載

---

### F. 日単位ステータス基盤の整備（day_status と overrides の使い分け）

- 目的

  - 「勤務不要日（公休日/会社休日/全休）」や「半休/無視」を、日単位の正規化テーブルで一貫管理
  - 従来どおり上書き（overrides）は監査可能なログとして残しつつ、参照側は常に「有効値（effective）」を使用

- 方針（読み取りは単一の論理ビューへ）

  - 基本は `day_status`（ベースライン: 1 ユーザー ×1 日 ×1 行, UNIQUE(user_id,date)）
  - 例外・一時的な変更は `day_status_overrides`（多段に記録、revoked_at で無効化）
  - 参照は `day_status_effective` ビューに統一（優先順位: override > baseline > 既定=work）
  - コード正規化: override の code（off_full/off_am/off_pm/ignore）を baseline の enum（off/am_off/pm_off/ignore）へマッピング

- 追加/変更（DB）

  - 追加: ビュー `day_status_effective(user_id, date, status, source, note)`
  - 既存: `day_status(status ENUM('work','off','am_off','pm_off','ignore'))` をベースラインの単一真実源に昇格
  - 既存: `day_status_overrides(status VARCHAR)` は「上書きログ」。最新の未取り消し（revoked_at IS NULL）を採用
  - インデックス: 既存の `(user_id,date)` と `(user_id,date,revoked_at)` を活用（クエリはユーザー × 月を主）

- 影響（読み取り側の統一）

  - アラート: `attendance_alerts.php`, `my_alerts.php` は `day_status_effective` を参照し、
    - 除外対象 = `status IN ('off','ignore')`（半休は除外しない）
  - 月次一覧: `attendance_list.html` も同様の基準でバッジ/集計を実施
  - 適用状況（2025-11-11）: アラート API は参照切替済（別イシュー ALERT-001）

- 書き込みルール

  - 通常運用: 管理者が会社休日/公休日を `day_status` に登録（ベースライン）
  - 一時対応: 管理者/本人が `day_status_overrides` を作成。API は従来の `day_status_set/clear` を継続利用
  - ベースラインの直接編集は管理者専用の新 API（後日）に限定（監査ログ併用）

- 受け入れ基準

  - 「未打刻」算出が休日/全休/無視を正しく除外（半休は対象）
  - 既存 API と後方互換（overrides のみ存在しても正しく反映）
  - ビュー作成のマイグレーション適用のみで参照統一が可能（段階導入）

- マイグレーション
  - `sql/migrations/2025-11-11_day_status_effective_view.sql` を追加
  - 内容: overrides を最新 1 件に集約し baseline とマージ、code を enum に正規化

---

### G. attendance_list 強化（非稼働日の明確化と操作フロー改善）

- 目的

  - 月次一覧で「勤務」「全休」「半休（午前/午後）」「無視」を一目で把握し、未打刻アラートや集計から非稼働日を適切に除外
  - 当日は直接編集、過去日は「修正申請」へ誘導し、監査性と運用整合性を高める

- UI/UX

  - 行先頭に日ステータスのバッジ表示（work/off/am_off/pm_off/ignore）
    - 色とアイコンで識別、ツールチップに由来（baseline/override）とメモ（note）
  - 当日（本人）: クイック分類コントロール（work / am_off / pm_off / off / ignore）
    - 既存 API `api/day_status_set.php`/`api/day_status_clear.php` を利用して override を設定/解除
    - 変更後は該当行の再計算（勤務時間/超過/欠落ラベル）を即時反映
  - 過去日: 直接編集リンクは非表示。かわりに「修正申請」ボタンを表示
    - 既存の申請ファイル群（issue_attendance_correction.md に準拠）に沿う
  - 欠落日の行（timecards なしかつ effective が work/半休）には強調＋「修正申請」ショートカット
  - 集計/メトリクス
    - 出勤日数: effective IN (work, am_off, pm_off) を含め、off/ignore は除外
    - 未打刻件数: effective IN (off, ignore) を除外（半休は対象）
    - 時間計算は現行ロジック踏襲。半休で timecards がある場合は実績ベース
  - 既知の小修正
    - overlay 要素の未定義参照を修正
    - 表示ラベルと値の整合（begins/ends の並び/命名）

- バリデーション（フロント＋サーバ）

  - 休憩は出退勤の範囲内、かつ休憩同士は非交差
  - 未来日不可、同日複数 timecard の禁止（既存準拠）
  - ハイライト（?date=YYYY-MM-DD）の自動展開は維持

- API/DB（読み取り統一）

  - 参照は `day_status_effective`（新設ビュー）に統一
  - 除外ポリシー: status IN ('off','ignore') をアラート/未打刻算出から除外、半休は除外しない
  - 既存 `api/day_status_get.php` の応答を拡張 or 新規 API で effective を返却（要設計）

- 受け入れ基準

  - 月次一覧で日ステータスの視認性が高く、当日のみ直接分類可能
  - 欠落日のカウントが休日/全休/無視を正しく除外
  - 過去日の編集導線は「修正申請」に一本化し、申請作成がワンタップ
  - 既存の勤務時間/超過計算と互換（半休は実績があれば時間計上）
  - 既知の小修正（overlay/ラベル）を解消

- 実装ステップ（推奨）
  1. day_status_effective の導入（完了）とアラート API の参照先切替（別課題）
  2. attendance_list.html にバッジ/分類 UI を追加（当日の override 設定）
  3. 欠落日の強調＋「修正申請」導線の追加
  4. 集計ロジックを effective に準拠させる（off/ignore 除外）
  5. 小修正（overlay/ラベル）

—
