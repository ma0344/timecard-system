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

## 運用メモ

- 本ファイルを一般ユーザー向けの単一の真実源とし、詳細はここで管理
- 全体版ロードマップ（`docs/ROADMAP.md`）には、要点・優先順位・リンクのみを記載
