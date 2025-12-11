<?php
/*
 * 目的: 有給の種別分類（全休/半休/時間単位等）を設定・取得します。
 * 入力: user_id、date、classification 等
 * 出力: 設定/取得結果
 */
?>
<?php
// api/paid_leave_classify.php
// Stage B: Classify user for paid leave rules (fulltime vs parttime buckets),
// evaluate eligibility (attendance >= 80% and minimum annual scheduled days),
// and return recommended grant days for the given milestone.
// Input JSON: { user_id:number, grant_date:'YYYY-MM-DD', start_date?:'YYYY-MM-DD' }
// Output JSON: {
//   user_id, grant_date, start_date, end_date, start_source,
//   classification: 'fulltime' | 'parttime:1d'|'parttime:2d'|'parttime:3d'|'parttime:4d',
//   milestone_label: string,
//   milestone_index: number,
//   default_days: number|null,
//   attendance_rate: number|null,
//   expected_days: number|null,
//   actual_work_days: number,
//   eligible_by_min_annual: boolean|null,
//   eligible_by_attendance: boolean|null,
//   notes: string[]
// }

session_start();
header('Content-Type: application/json');
require_once '../db_config.php';

function parse_milestone_to_months($s) {
  if (!$s) return 0;
  $m = strtolower(preg_replace('/\s+/', '', (string)$s));
  $pos = strpos($m, 'y');
  if ($pos !== false) {
    $y = intval(substr($m, 0, $pos));
    $rest = substr($m, $pos + 1);
    $mm = intval(preg_replace('/[^0-9]/', '', $rest));
    return $y * 12 + $mm;
  }
  return intval(preg_replace('/[^0-9]/', '', $m));
}

try {
  if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'not logged in']);
    exit;
  }
  // Admin only
  $adminId = $_SESSION['user_id'];
  $stmt = $pdo->prepare('SELECT role FROM users WHERE id = ?');
  $stmt->execute([$adminId]);
  $admin = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$admin || $admin['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'forbidden']);
    exit;
  }

  $raw = file_get_contents('php://input');
  $j = json_decode($raw, true);
  $userId = isset($j['user_id']) ? (int)$j['user_id'] : 0;
  $grantDate = isset($j['grant_date']) ? trim($j['grant_date']) : '';
  $startOverride = isset($j['start_date']) ? trim($j['start_date']) : '';
  if ($userId <= 0 || !$grantDate) {
    http_response_code(400);
    echo json_encode(['error' => 'user_id and grant_date required']);
    exit;
  }

  // Load settings: paid_leave_rules JSON
  $stmt = $pdo->query('SELECT paid_leave_rules FROM settings LIMIT 1');
  $settings = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
  $rules = null;
  if (!empty($settings['paid_leave_rules'])) {
    $tmp = json_decode($settings['paid_leave_rules'], true);
    if (json_last_error() === JSON_ERROR_NONE) $rules = $tmp;
  }
  // Milestones and tables
  $milestones = isset($rules['milestones']) && is_array($rules['milestones']) ? $rules['milestones'] : ['6m', '1y6m', '2y6m', '3y6m', '4y6m', '5y6m', '6y6m+'];
  $full = isset($rules['fulltime']) && is_array($rules['fulltime']) ? $rules['fulltime'] : [];
  $part = isset($rules['parttime']) && is_array($rules['parttime']) ? $rules['parttime'] : [];
  $thresholds = isset($rules['thresholds']) && is_array($rules['thresholds']) ? $rules['thresholds'] : [];
  $PT_HOURS_LT = isset($thresholds['weekly_hours_lt']) ? (float)$thresholds['weekly_hours_lt'] : 30.0;
  $PT_DAYS_MAX = isset($thresholds['weekly_days_max']) ? (int)$thresholds['weekly_days_max'] : 4;
  $PT_ANNUAL_MAX = isset($thresholds['annual_days_max']) ? (int)$thresholds['annual_days_max'] : 216;
  $MIN_ANY_GRANT = isset($thresholds['min_any_grant']) ? (int)$thresholds['min_any_grant'] : 48;

  // Load user details (hire date and scheduled fields)
  $stmt = $pdo->prepare('SELECT hire_date, scheduled_weekly_days, scheduled_weekly_hours, scheduled_annual_days, full_time FROM user_detail WHERE user_id = ?');
  $stmt->execute([$userId]);
  $ud = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

  // Determine end_date (grant_date - 1 day)
  $end = new DateTime($grantDate);
  $end->modify('-1 day');
  $endDate = $end->format('Y-m-d');

  // Determine start_date: same policy as attendance_avg.php
  $stmt = $pdo->prepare('SELECT grant_date FROM paid_leaves WHERE user_id = ? AND grant_date < ? ORDER BY grant_date DESC, id DESC LIMIT 1');
  $stmt->execute([$userId, $endDate]);
  $prev = $stmt->fetch(PDO::FETCH_ASSOC);

  $startByYear = clone $end;
  $startByYear->modify('-1 year');
  $startByYear->modify('+1 day');
  $startByHalf = clone $end;
  $startByHalf->modify('-6 months');
  $startByHalf->modify('+1 day');
  $start = $startByHalf;
  $startSource = 'auto';
  if ($startOverride) {
    $tmp = DateTime::createFromFormat('Y-m-d', $startOverride);
    if (!$tmp || $tmp > $end) {
      http_response_code(400);
      echo json_encode(['error' => 'invalid start_date']);
      exit;
    }
    $start = $tmp;
    $startSource = 'override';
  } else if ($prev && !empty($prev['grant_date'])) {
    $prevDt = new DateTime($prev['grant_date']);
    $start = ($prevDt > $startByYear) ? $prevDt : $startByYear;
    $startSource = 'prev_grant_or_1y';
  } else if (!empty($ud['hire_date'])) {
    $hire = new DateTime($ud['hire_date']);
    $start = ($hire > $startByHalf) ? $hire : $startByHalf;
    $startSource = 'hire_or_6m';
  }
  $startDate = $start->format('Y-m-d');

  // Aggregate actual work days in period (reuse logic of attendance_avg)
  $sql = "WITH daily AS (
                SELECT t.user_id, t.work_date,
                       GREATEST(0,
                         TIMESTAMPDIFF(MINUTE, t.clock_in, t.clock_out)
                         - IFNULL(bm.break_minutes, 0)
                       ) AS minutes
                FROM timecards t
                LEFT JOIN (
                    SELECT timecard_id,
                           SUM(TIMESTAMPDIFF(MINUTE, break_start, break_end)) AS break_minutes
                    FROM breaks
                    GROUP BY timecard_id
                ) bm ON bm.timecard_id = t.id
                WHERE t.user_id = :uid
                  AND t.work_date BETWEEN :start AND :end
                  AND t.clock_in IS NOT NULL AND t.clock_out IS NOT NULL
           )
           SELECT COUNT(*) AS work_days,
                  COALESCE(SUM(minutes),0) AS total_minutes
           FROM daily";
  $stmt = $pdo->prepare($sql);
  $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
  $stmt->bindValue(':start', $startDate, PDO::PARAM_STR);
  $stmt->bindValue(':end', $endDate, PDO::PARAM_STR);
  $stmt->execute();
  $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['work_days' => 0, 'total_minutes' => 0];
  $workDays = (int)$row['work_days'];

  // Period length and weeks
  $d1 = new DateTime($startDate);
  $d2 = new DateTime($endDate);
  $periodDays = max(1, (int)$d1->diff($d2)->format('%a') + 1);
  $weeks = $periodDays / 7.0;

  // Expected scheduled days in period (for 8割判定)
  $schedWeeklyDays = isset($ud['scheduled_weekly_days']) ? (int)$ud['scheduled_weekly_days'] : null;
  $schedWeeklyHours = isset($ud['scheduled_weekly_hours']) ? (float)$ud['scheduled_weekly_hours'] : null;
  $schedAnnualDays = isset($ud['scheduled_annual_days']) ? (int)$ud['scheduled_annual_days'] : null;

  $expectedDays = null;
  if ($schedAnnualDays && $schedAnnualDays > 0) {
    $expectedDays = $schedAnnualDays * ($periodDays / 365.0);
  } else if ($schedWeeklyDays && $schedWeeklyDays > 0) {
    $expectedDays = $schedWeeklyDays * $weeks;
  }

  $attendanceRate = null;
  $eligibleByAttendance = null;
  if ($expectedDays && $expectedDays > 0) {
    $attendanceRate = $workDays / $expectedDays;
    $eligibleByAttendance = ($attendanceRate >= 0.8);
  }

  // Determine classification (fulltime or parttime bucket)
  $notes = [];
  $classification = 'fulltime';
  $annualForBucket = null;
  if ($schedAnnualDays && $schedAnnualDays > 0) {
    $annualForBucket = $schedAnnualDays;
  } else if ($schedWeeklyDays && $schedWeeklyDays > 0) {
    $annualForBucket = (int)round($schedWeeklyDays * 52.0);
    $notes[] = 'annual days approximated from weekly days';
  }

  $isPart = false;
  $bucket = '4d';
  // Employment full-time override: if full_time==1, force classification as fulltime
  $employmentFullTime = isset($ud['full_time']) ? ((int)$ud['full_time'] === 1) : false;
  if ($employmentFullTime) {
    $classification = 'fulltime';
    $isPart = false;
    $notes[] = 'employment full_time override';
  } else {
    if ($schedWeeklyHours !== null && $schedWeeklyDays !== null) {
      if (($schedWeeklyHours < $PT_HOURS_LT && $schedWeeklyDays <= $PT_DAYS_MAX) ||
        ($annualForBucket !== null && $annualForBucket <= $PT_ANNUAL_MAX)
      ) {
        $isPart = true;
      }
    } else if ($annualForBucket !== null && $annualForBucket <= $PT_ANNUAL_MAX) {
      $isPart = true;
    }

    if ($annualForBucket !== null) {
      if ($annualForBucket >= 169) $bucket = '4d';
      else if ($annualForBucket >= 121) $bucket = '3d';
      else if ($annualForBucket >= 73) $bucket = '2d';
      else if ($annualForBucket >= 48) $bucket = '1d';
      else $bucket = 'none';
    }

    if ($isPart && $bucket === 'none') {
      $isPart = false;
    }

    if ($isPart) $classification = 'parttime:' . $bucket;
  }

  // Minimum annual scheduled days eligibility
  $eligibleByMinAnnual = null;
  if ($annualForBucket !== null) {
    $eligibleByMinAnnual = ($annualForBucket >= $MIN_ANY_GRANT);
  }

  // Milestone index by months since hire to grant
  $milMonths = array_map('parse_milestone_to_months', $milestones);
  $msIdx = 0;
  $msLabel = $milestones[0];
  if (!empty($ud['hire_date'])) {
    $h = new DateTime($ud['hire_date']);
    $g = new DateTime($grantDate);
    $months = ($g->format('Y') - $h->format('Y')) * 12 + ($g->format('n') - $h->format('n'));
    if ((int)$g->format('j') < (int)$h->format('j')) $months -= 1;
    for ($i = 0; $i < count($milMonths); $i++) {
      if ($months >= $milMonths[$i]) {
        $msIdx = $i;
        $msLabel = $milestones[$i];
      } else {
        break;
      }
    }
  }

  // Choose default days
  $defaultDays = null;
  if (($eligibleByMinAnnual !== false) && ($eligibleByAttendance !== false)) {
    if ($classification === 'fulltime') {
      if (isset($full[$msIdx])) $defaultDays = $full[$msIdx];
    } else {
      $key = explode(':', $classification)[1];
      if (isset($part[$key]) && is_array($part[$key]) && isset($part[$key][$msIdx])) {
        $defaultDays = $part[$key][$msIdx];
      }
    }
  }

  echo json_encode([
    'user_id' => $userId,
    'grant_date' => $grantDate,
    'start_date' => $startDate,
    'end_date' => $endDate,
    'start_source' => $startSource,
    'classification' => $classification,
    'milestone_label' => $msLabel,
    'milestone_index' => $msIdx,
    'default_days' => $defaultDays,
    'attendance_rate' => $attendanceRate,
    'expected_days' => $expectedDays,
    'actual_work_days' => $workDays,
    'eligible_by_min_annual' => $eligibleByMinAnnual,
    'eligible_by_attendance' => $eligibleByAttendance,
    'notes' => $notes,
  ]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error' => 'server error', 'message' => $e->getMessage()]);
}
