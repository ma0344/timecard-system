<?php
// api/flex_summary.php
session_start();
header('Content-Type: application/json');
require_once '../db_config.php';
require_once __DIR__ . '/_lib_worktime.php';

try {
  if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'not logged in']);
    return;
  }
  $userId = (int)$_SESSION['user_id'];

  // 利用者の雇用区分を確認（常勤以外は対象外）
  $stmt = $pdo->prepare('SELECT COALESCE(d.full_time, 1) AS full_time FROM users u LEFT JOIN user_detail d ON u.id = d.user_id WHERE u.id = ?');
  $stmt->execute([$userId]);
  $userRow = $stmt->fetch(PDO::FETCH_ASSOC);
  $isFullTime = $userRow ? ((int)$userRow['full_time'] === 1) : true;

  if (!$isFullTime) {
    echo json_encode([
      'ok' => true,
      'is_full_time' => false,
      'message' => 'flex summary is not applicable for part-time users'
    ]);
    return;
  }

  // 設定値取得
  $settingsSql = "SELECT period_start, period_end, rounding_type, rounding_unit, work_hours, work_minutes, legal_hours_28, legal_hours_29, legal_hours_30, legal_hours_31 FROM settings ORDER BY id DESC LIMIT 1";
  $settingsStmt = $pdo->query($settingsSql);
  $settingsRow = $settingsStmt ? $settingsStmt->fetch(PDO::FETCH_ASSOC) : false;
  if ($settingsRow) {
    $periodStart = max(1, (int)$settingsRow['period_start']);
    $periodEnd = max(1, (int)$settingsRow['period_end']);
    $roundingType = $settingsRow['rounding_type'] ?: 'floor';
    $roundingUnit = max(1, (int)$settingsRow['rounding_unit']);
    $legalHoursMap = [
      28 => (float)$settingsRow['legal_hours_28'],
      29 => (float)$settingsRow['legal_hours_29'],
      30 => (float)$settingsRow['legal_hours_30'],
      31 => (float)$settingsRow['legal_hours_31'],
    ];
  } else {
    // デフォルト
    $periodStart = 1;
    $periodEnd = 31;
    $roundingType = 'floor';
    $roundingUnit = 1;
    $legalHoursMap = [
      28 => 160.0,
      29 => 165.0,
      30 => 171.0,
      31 => 177.0,
    ];
  }

  // 対象月（period_end が属する月）
  $monthParam = isset($_GET['month']) ? trim($_GET['month']) : '';
  $targetMonth = null; // DateTime (first day of month)
  if ($monthParam !== '' && preg_match('/^\d{4}-\d{2}$/', $monthParam)) {
    $targetMonth = DateTime::createFromFormat('Y-m-d', $monthParam . '-01');
  }
  if (!$targetMonth) {
    $targetMonth = new DateTime('now');
    // 現在日がどの月度に属するか判定（period_end ベース）
    $today = new DateTime('today');
    $periodEndDay = min($periodEnd, (int)$today->format('t'));
    $periodEndDate = DateTime::createFromFormat('Y-m-d', $today->format('Y-m-') . str_pad((string)$periodEndDay, 2, '0', STR_PAD_LEFT));
    if ($today > $periodEndDate) {
      // 次の月度
      $targetMonth = (clone $today)->modify('first day of next month');
    } else {
      $targetMonth = DateTime::createFromFormat('Y-m-d', $today->format('Y-m-01'));
    }
  }

  // 共通ライブラリで集計（有休を含める、全休/ignore除外、半休含める）
  $monthInfo = aggregate_work_summary(
    $pdo,
    $userId,
    $targetMonth,
    $periodStart,
    $periodEnd,
    $legalHoursMap,
    [
      'includePaidLeave' => true,
      'excludeStatuses' => ['off', 'off_full', 'ignore'],
      'includeHalfDay' => true,
      'workdayDefinition' => 'require_in_out',
    ]
  );
  $isSettlementMonth = in_array((int)$targetMonth->format('n'), [2, 4, 6, 8, 10, 12], true);

  $result = [
    'ok' => true,
    'is_full_time' => true,
    'month_label' => $monthInfo['label'],
    'period' => [
      'start' => $monthInfo['period_start'],
      'end' => $monthInfo['period_end'],
    ],
    'legal_minutes' => $monthInfo['legal_minutes'],
    'worked_minutes' => $monthInfo['worked_minutes'],
    'paid_leave_minutes' => $monthInfo['paid_leave_minutes'],
    'delta_minutes' => $monthInfo['delta_minutes'],
    'is_settlement_month' => $isSettlementMonth,
  ];

  if ($isSettlementMonth) {
    $prevMonth = (clone $targetMonth)->modify('-1 month');
    $prevInfo = aggregate_work_summary(
      $pdo,
      $userId,
      $prevMonth,
      $periodStart,
      $periodEnd,
      $legalHoursMap,
      [
        'includePaidLeave' => true,
        'excludeStatuses' => ['off', 'off_full', 'ignore'],
        'includeHalfDay' => true,
        'workdayDefinition' => 'require_in_out',
      ]
    );
    $result['two_month'] = [
      'month_labels' => [$prevInfo['label'], $monthInfo['label']],
      'legal_minutes' => $prevInfo['legal_minutes'] + $monthInfo['legal_minutes'],
      'worked_minutes' => $prevInfo['worked_minutes'] + $monthInfo['worked_minutes'],
      'paid_leave_minutes' => $prevInfo['paid_leave_minutes'] + $monthInfo['paid_leave_minutes'],
      'delta_minutes' => ($prevInfo['delta_minutes'] + $monthInfo['delta_minutes']),
      'periods' => [
        ['start' => $prevInfo['period_start'], 'end' => $prevInfo['period_end']],
        ['start' => $monthInfo['period_start'], 'end' => $monthInfo['period_end']],
      ],
    ];
  }

  // 表示用丸め（H:MM）に備えて追加情報
  $result['display'] = [
    'rounding_type' => $roundingType,
    'rounding_unit' => $roundingUnit,
  ];

  echo json_encode($result);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error' => 'server error', 'message' => $e->getMessage()]);
}

// 以下、旧個別ロジック（buildMonthSummary/aggregateWorkedMinutes 等）は
// 共通ライブラリへ移行済みのため削除しました。