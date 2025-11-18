# [ATT-001] attendance_list 強化（非稼働日の明確化と操作フロー改善）

ステータス: In Progress
作成日: 2025-11-11
担当: 未割当
関連: docs/ROADMAP_USER_UI.md#G-attendance_list-強化（非稼働日の明確化と操作フロー改善）, sql/migrations/2025-11-11_day_status_effective_view.sql

## 背景 / 課題

- 現状、日単位の状態は `day_status_overrides` のみを参照しており、`day_status`（ベースライン）は未活用。
- 未打刻アラートや月次集計において、休日/全休/無視が統一的に除外されていない。
- `attendance_list.html` は編集/追加/削除は可能だが、日区分（勤務/全休/半休/無視）の視認と操作が限定的。
- 当日の即時操作と過去日の監査性（修正申請）の切り分けが不明瞭。

## 目的

- 月次一覧で日区分を一目で把握し、非稼働日を未打刻判定・集計から適切に除外。
- 当日は直接分類（override 設定）、過去日は「修正申請」導線で監査性を確保。

## スコープ

- フロント: `attendance_list.html` の UI 強化（バッジ、分類コントロール、欠落日の強調、導線）。
- サーバ: 日区分の読み取りを `day_status_effective` に統一。必要なら `api/day_status_get.php` を拡張。
- 小修正: overlay 要素未定義参照の修正、ラベル/値の整合。

## 非スコープ（別イシュー）

- 管理者 UI からのベースライン一括投入（会社休日など）
- アラート API（`api/my_alerts.php`, `api/attendance_alerts.php`）の参照切替
- 勤怠修正申請機能の詳細拡張（承認画面など）

## 仕様（2025-11-14 更新）

### 日ステータス（表示/解釈）

- 有効値は `day_status_effective` ビューを使用。
- ステータス種別と扱い:
  - work: 通常日（未打刻対象）
  - am_off/pm_off: 半休（未打刻対象。ただし表示で区別）
  - off: 全休（未打刻から除外）
  - ignore: 集計/アラートから除外

### UI/UX

- 行先頭: ステータスバッジ（色＋アイコン）。ツールチップに source（baseline/override）と note。
- 当日（本人）: 分類コントロール（work / am_off / pm_off / off / ignore）。
  - API: 本人用 `api/day_status_self_set.php` / `api/day_status_self_clear.php` で override を更新。
  - 成功後は当該行とサマリを再計算。
- 過去日: 直接編集を隠し、「修正申請」ボタンを表示。
- 欠落日（work/半休 かつ timecards なし）: 強調表示＋「修正申請」ショートカット。
  - 現状は「＋」による新規追加パネルを行内展開（修正申請ボタンは後続）。
- 欠落行（未打刻）でのクイック操作: 「全休」ボタンを追加し、即時に全休（override=off_full）へ切替できるようにする（2025-11-16 実装済）。
- 集計: 出勤日数は実記録（出退勤データ）ありの日をカウント、off/ignore は除外。未打刻件数は off/ignore を除外（半休は対象）。

### バリデーション

- 休憩は出退勤の間に含み、互いに非交差。
- 未来日不可。同日複数レコード不可（既存踏襲）。
- クエリ `?date=YYYY-MM-DD` の自動展開を維持。

### 表示ポリシー（2025-11-15 追記）

- 未到達日（今日より後）について:
  - 未打刻の行は一覧に表示しない（ノイズ低減のため）。
  - 有給が承認済み（または履歴に存在）の日は、未到達日でも「有給」として表示する。
  - 深リンクでの強制表示（forceVisible）やハイライト指定はこの制限より優先。

## API/DB（暫定）

- 読み取り: `day_status_effective`（新ビュー）を参照。
  - 一覧側は暫定 API `api/day_status_effective_get.php` を利用（将来 `day_status_get` に統合予定）。
- 既存 API の拡張案:
  - GET `api/day_status_get.php?start=YYYY-MM-DD&end=YYYY-MM-DD&effective=1`
    - 応答: [{user_id, date, status, source, note}]
- 書き込み: 既存の `api/day_status_set.php`/`api/day_status_clear.php` を継続利用（override 更新）。

## 受け入れ基準（Acceptance Criteria）

[x] 月次一覧に日バッジが表示され、当日は分類をワンタップで切替可能。
[ ] 欠落日のカウントと強調が休日/全休/無視を正しく除外（半休は対象）。
[ ] 過去日は直接編集ではなく「修正申請」に誘導できる。
[x] overlay 参照のエラーが発生しない。表示ラベル/値の不整合がない。
[x] 既存の時間計算・合計と互換性を維持（現行ロジック踏襲）。

注記（集計の解釈、2025-11-16 確定）:

- 出勤日数 = 「実記録（出退勤データ）ありの日」をカウント（確定）。
  - effective が off/ignore の日は集計から除外。
  - 未打刻（実記録なし）の日は出勤日数にカウントしない。
  - 半休で実記録がある場合は +1 日としてカウント。
- 未打刻件数 = effective が work/am_off/pm_off で実記録なし、かつ今日以前、かつ有給でない日をカウント。

## 実装ステップ

1. `day_status_effective` の導入（完了）
2. `attendance_list.html` にバッジ/分類 UI を追加
3. 欠落日の強調と「修正申請」導線を追加
4. 集計ロジックを effective に準拠（off/ignore 除外）
5. 小修正（overlay/ラベル）

## タスク（チェックリスト）

- [x] フロント: バッジ表示の実装（ツールチップ含む）
- [x] フロント: 当日の分類コントロール（self_set/self_clear で更新）
- [ ] フロント: 欠落日の強調と修正申請ボタン（現状は「＋」で新規追加パネル）
- [x] フロント: 集計ロジックの見直し（off/ignore 除外）
- [x] フロント: overlay/ラベルの不具合修正
- [ ] API: `day_status_get` を effective 応答に対応（暫定 API を後続統合）
- [x] ドキュメント更新（操作説明と除外ルール）

### 追加（2025-11-15 追記）

- [x] フロント: 未来日の未打刻を非表示（forceVisible/hightlight 指定時を除く）
- [x] フロント: 未到達日の有給は「有給」として表示（`paid_leave_history` 反映）
- [x] フロント: 欠落行に「全休」クイックボタンを追加（override=off_full の即時適用）
- [x] フロント: 未打刻件数の算出（off/ignore 除外、半休は対象）を Stats に反映
- [ ] 集計仕様の決定: 出勤日数を「実績」と「計画」の二指標に分離するか再定義 → 2025-11-16 確定: 「実績（実記録あり）」として実装済

### 追加（2025-11-14）

- [x] ディープリンク安定化（強制可視化＋遅延ハイライト解除）
- [x] `back`/`backMode` の戻り導線（保存/キャンセル/閉じる/削除/復元で auto 戻り）

## 影響 / リスク

- ステータスの解釈が UI と API でずれるリスク → ビュー（effective）に統一。
- 半休日の扱い（未打刻対象）に関する運用合意が必要。

## 参考

- `docs/ROADMAP_USER_UI.md` セクション F/G
- `api/my_alerts.php`, `api/attendance_alerts.php`（将来的に effective に切替）
