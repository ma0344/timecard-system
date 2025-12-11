<?php
// api/_lib_worktime.php
// 共通：勤務時間計算ユニット（休憩控除・日ステータス除外・有休集計・期間生成）

declare(strict_types=1);

/**
 * 期間生成（暦ベース: 前月 period_start ～ 対象月 period_end）
 */
function build_calendar_period(DateTime $anchorMonthEnd, int $periodStart, int $periodEnd): array {
  $endYear = (int)$anchorMonthEnd->format('Y');
  $endMonth = (int)$anchorMonthEnd->format('n');
  $prevMonth = (clone $anchorMonthEnd)->modify('-1 month');
  $startYear = (int)$prevMonth->format('Y');
  $startMonth = (int)$prevMonth->format('n');

  $startDay = min($periodStart, lib_days_in_month($startYear, $startMonth));
  $endDay = min($periodEnd, lib_days_in_month($endYear, $endMonth));

  return [
    'start' => sprintf('%04d-%02d-%02d', $startYear, $startMonth, $startDay),
    'end' => sprintf('%04d-%02d-%02d', $endYear, $endMonth, $endDay),
  ];
}

/**
 * 実働分（休憩控除）を集計。既定オプション:
 * - excludeStatuses: ['off','off_full','ignore'] を除外
 * - includeHalfDay: true（半休は含める）
 * - workdayDefinition: 'require_in_out'（clock_in/out両方必須）
 */
function compute_work_minutes(PDO $pdo, int $userId, string $startDate, string $endDate, array $opts = []): int {
  $excludeStatuses = $opts['excludeStatuses'] ?? ['off', 'off_full', 'ignore'];
  // day_status_effective は off_full を off に正規化する前提のため、既定は ['off','ignore']
  $excludeStatuses = $opts['excludeStatuses'] ?? ['off', 'ignore'];
  $includeHalfDay = $opts['includeHalfDay'] ?? true;
  $requireInOut = ($opts['workdayDefinition'] ?? 'require_in_out') === 'require_in_out';

  // day_status_effective で除外（半休は集合に含めない）
  $placeholders = implode(',', array_fill(0, count($excludeStatuses), '?'));
  $sql = "WITH scoped_timecards AS (
                SELECT id, user_id, work_date, clock_in, clock_out
                FROM timecards
                WHERE user_id = ?
                  AND work_date BETWEEN ? AND ?
                  " . ($requireInOut ? "AND clock_in IS NOT NULL AND clock_out IS NOT NULL" : '') . "
            ),
            breaks_sum AS (
                SELECT b.timecard_id, SUM(TIMESTAMPDIFF(MINUTE, b.break_start, b.break_end)) AS break_minutes
                FROM breaks b
                INNER JOIN scoped_timecards st ON st.id = b.timecard_id
                GROUP BY b.timecard_id
            ),
            base AS (
                SELECT st.id, st.user_id, st.work_date,
                       GREATEST(0,
                           TIMESTAMPDIFF(MINUTE, st.clock_in, st.clock_out)
                           - IFNULL(b.break_minutes, 0)
                       ) AS minutes
                FROM scoped_timecards st
                LEFT JOIN breaks_sum b ON b.timecard_id = st.id
            )
            SELECT COALESCE(SUM(b.minutes), 0) AS total_minutes
            FROM base b
            LEFT JOIN day_status_effective dse
              ON dse.user_id = b.user_id AND dse.date = b.work_date
                 AND dse.status IN ($placeholders)
            WHERE dse.user_id IS NULL";

  $stmt = $pdo->prepare($sql);
  $paramIdx = 1;
  // scoped_timecards CTE のバインド
  $stmt->bindValue($paramIdx++, $userId, PDO::PARAM_INT);
  $stmt->bindValue($paramIdx++, $startDate, PDO::PARAM_STR);
  $stmt->bindValue($paramIdx++, $endDate, PDO::PARAM_STR);
  foreach ($excludeStatuses as $st) {
    $stmt->bindValue($paramIdx++, $st, PDO::PARAM_STR);
  }
  $stmt->execute();
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  return (int)($row ? $row['total_minutes'] : 0);
}

/**
 * 有休利用合計（分）。`paid_leave_use_events.total_hours` を分換算。
 */
function compute_paid_leave_minutes(PDO $pdo, int $userId, string $startDate, string $endDate): int {
  $sql = "SELECT COALESCE(SUM(total_hours), 0) AS sum_hours
            FROM paid_leave_use_events
            WHERE user_id = :uid
              AND used_date BETWEEN :start AND :end";
  $stmt = $pdo->prepare($sql);
  $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
  $stmt->bindValue(':start', $startDate, PDO::PARAM_STR);
  $stmt->bindValue(':end', $endDate, PDO::PARAM_STR);
  $stmt->execute();
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  $hours = $row ? (float)$row['sum_hours'] : 0.0;
  return (int)round($hours * 60);
}

/**
 * 法定分（開始月の暦日数に基づく法定時間マップ hours→minutes）
 */
function compute_legal_minutes(array $legalHoursMap, int $startYear, int $startMonth): int {
  $days = lib_days_in_month($startYear, $startMonth);
  $legalHours = $legalHoursMap[$days] ?? ($legalHoursMap[31] ?? 0.0);
  return (int)round(((float)$legalHours) * 60);
}

/**
 * 汎用：月度サマリ（flex向け）
 */
function aggregate_work_summary(PDO $pdo, int $userId, DateTime $periodEndMonth, int $periodStart, int $periodEnd, array $legalHoursMap, array $opts = []): array {
  $period = build_calendar_period($periodEndMonth, $periodStart, $periodEnd);

  // 開始月（法定分に利用）
  $prevMonth = (clone $periodEndMonth)->modify('-1 month');
  $startYear = (int)$prevMonth->format('Y');
  $startMonth = (int)$prevMonth->format('n');

  $worked = compute_work_minutes($pdo, $userId, $period['start'], $period['end'], $opts);
  $paid = !empty($opts['includePaidLeave']) ? compute_paid_leave_minutes($pdo, $userId, $period['start'], $period['end']) : 0;
  $legal = compute_legal_minutes($legalHoursMap, $startYear, $startMonth);

  return [
    'label' => sprintf('%d年%d月度', (int)$periodEndMonth->format('Y'), (int)$periodEndMonth->format('n')),
    'period_start' => $period['start'],
    'period_end' => $period['end'],
    'worked_minutes' => $worked,
    'paid_leave_minutes' => $paid,
    'legal_minutes' => $legal,
    'delta_minutes' => ($worked + $paid) - $legal,
  ];
}

/**
 * 補助：月の日数算出（拡張非依存）
 */
function lib_days_in_month(int $year, int $month): int {
  $year = max(1, $year);
  $month = (($month - 1) % 12) + 1;
  $dt = DateTime::createFromFormat('!Y-n-j', sprintf('%04d-%d-1', $year, $month));
  if ($dt instanceof DateTime) {
    return (int)$dt->format('t');
  }
  return 30;
}
