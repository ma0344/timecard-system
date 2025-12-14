# [ATT-003] attendance_list リファクタ: `loadRecordsByPeriod` の責務分割（取得/適用/描画）

ステータス: Done
作成日: 2025-12-13
更新日: 2025-12-14
担当: 未割当
関連: `attendance_list.html`, `api/attendance_list.php`, `api/day_status_effective_get.php`, `api/period_lock_effective_get.php`, `api/paid_leave_history.php`

## 背景 / 課題

- `loadRecordsByPeriod(monthVal)` が以下を1関数で実施している。
  - 期間計算（start/end）
  - 勤怠レコード取得（records）
  - status/lock/paid_leave の並列取得とエラーハンドリング
  - グローバル状態（`__periodRange`/`__dayList`/`__recordMap`/`__dayStatuses`/`__lockedRanges`/`__paidLeave`）の更新
  - `setYearLabel` / `renderStatsBox` / `render` / `refreshFlexSummary` の呼び出し
- 「データ取得」か「UI更新」かが混在し、機能追加/バグ修正時に影響範囲が読みにくい。

## 目的

- データ取得とUI更新を分離し、関数の見通しと再利用性を上げる。
- 取得エラー時の初期化（空配列/空オブジェクト）を一貫させる。

## スコープ

- フロントのみ: `attendance_list.html` の関数分割
- 既存API・既存レスポンスを前提に整理（API変更なし）

## 非スコープ

- API統合（effective系の統合など）
- キャッシュ/ローカル保存などの機能追加

## 提案アプローチ

- 分割案:
  - `fetchAttendanceRecords({start,end}) => records`
  - `fetchAuxData({start,end}) => {dayStatuses, lockedRanges, paidLeave}`
  - `applyPeriodDerivedState(periodRange, records)`（`__periodRange`/`__dayList`/`__recordMap`）
  - `applyAuxState(aux)`（`__dayStatuses`/`__lockedRanges`/`__paidLeave`）
  - `renderPeriodUI(monthVal)`（`setYearLabel`/stats/render/flex）
- 既存のPromise.all並列取得は維持。

## 受け入れ基準（Acceptance Criteria）

- 既存の表示・フィルタ・インライン展開が同じ。
- エラー時に `__dayStatuses`/`__lockedRanges`/`__paidLeave` が必ず初期化され、例外が出ない。
- `loadRecordsByPeriod` は「fetch→apply→render」の流れが上から読める。

## 実装ステップ

1. `fetchAttendanceRecords` / `fetchAuxData` の抽出
2. `applyPeriodDerivedState` / `applyAuxState` に責務分割
3. stats/render/flex 呼び出しを `renderPeriodUI` に集約
4. 手動テスト（期間切替、フィルタ切替、SSE/承認反映）

## 影響 / リスク

- グローバル変数依存が多いため、分割時に更新順序が変わるとバグりやすい → 現状の順序を保持。
