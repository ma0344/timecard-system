# 勤務時間算定の共通ライブラリ導入方針

本プロジェクトでは、勤務時間算定（休憩控除・有給の扱い・日ステータス除外・期間生成）を一元化するため、`api/_lib_worktime.php` を導入しました。これにより、各 API で算定式の差異による不整合を防ぎます。

## 目的

- 勤務時間算定ロジックの重複排除と一貫性確保
- オプション切替で画面/用途ごとの要件に柔軟に対応
- `day_status_effective` ビュー必須運用に合わせた除外判定の統一

## ビュー運用（必須）

- `day_status_effective` ビューを全環境で必須運用とします。
- ビュー側で `off_full` は `off` に正規化される前提で、除外集合は `('off','ignore')` を既定とします（半休は除外しない）。
- フォールバック（ビュー無し）はサポート対象外。整合性低下のため採用しません。

## 単位整合ポリシー

- API は分（minutes）を標準単位として返します。
- 丸めは原則 UI 側で適用（`rounding_type`/`rounding_unit`）。CSV や外部連携でサーバ丸めが必要な場合のみ、関数オプションで ON にする拡張を検討可能（既定 OFF）。
- 有休はドメイン上 hours が自然ですが、集計で返す値は分へ変換して統一します（例: フレックス差分）。

## ライブラリ概要（`api/_lib_worktime.php`）

- `build_calendar_period(DateTime $anchorMonthEnd, int $periodStart, int $periodEnd): array`
  - 前月 period_start ～ 対象月 period_end を生成
- `compute_work_minutes(PDO $pdo, int $userId, string $startDate, string $endDate, array $opts = []): int`
  - 休憩控除＋`day_status_effective`除外で実働分（分）を返す
  - 既定オプション: `excludeStatuses=['off','ignore']`, `includeHalfDay=true`, `workdayDefinition='require_in_out'`
- `compute_paid_leave_minutes(PDO $pdo, int $userId, string $startDate, string $endDate): int`
  - `paid_leave_use_events.total_hours` を分へ換算
- `compute_legal_minutes(array $legalHoursMap, int $startYear, int $startMonth): int`
  - 開始月日数に応じて法定時間（hours）→ 分換算
- `aggregate_work_summary(PDO $pdo, int $userId, DateTime $periodEndMonth, int $periodStart, int $periodEnd, array $legalHoursMap, array $opts = []): array`
  - 月度サマリ（label/period/worked/paid/legal/delta）を返す

## 置換状況（2025-12-03）

- 置換済み
  - `api/flex_summary.php`: 個別算定を廃止し `aggregate_work_summary` を使用（有休含む）
  - `api/attendance_avg.php`: 期間ロジックは維持し、分集計を `compute_work_minutes` に置換（有休含まず）
  - `api/attendance_alerts.php`: 除外集合を `('off','ignore')` に統一、ビュー前提
  - `api/my_alerts.php`: 同上、除外集合統一＋ライブラリ読込
- 対象外/保留
  - `api/dashboard_summary.php`: 現状は勤務時間集計を行っていないため置換対象なし。必要ならダッシュボードへ「今月度の総実働・未打刻件数」などの拡張でライブラリを利用可能。

## アラート系の整合化

- 欠勤判定は `day_status_effective` の除外日（`off`/`ignore`）を期間内から差し引き、present（打刻あり）との差で missing を算出。
- 半休（`am_off`/`pm_off`）は除外せず対象に含める。

## 今後の拡張案

- `compute_daily_stats(...)` を追加し、日別の就業分/休憩分/ステータスの配列を返せるようにして分析系 API を統一。
- サーバ丸め（分 → 丸め単位）をオプション化して CSV/帳票用に対応。

## 運用メモ

- 期間生成の違い（暦ベース vs 付与/入社アンカー）を API 側で明示しつつ、分集計は常に共通関数を使用。
- ビュー（`day_status_effective`）が前提のため、DB セットアップ手順にビュー作成を含めること。
