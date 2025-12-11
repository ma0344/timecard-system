<?php
/*
 * 目的: 指定期間の平均勤務時間（分）を算出して返します。
 * 入力: 期間、ユーザー（セッション）
 * 出力: 平均分数、対象日数など
 */
?>
<?php
// api/attendance_avg.php
// 指定ユーザーの期間平均（休憩控除後）1日あたり時間を返す
// ポリシー: 期間は「前回付与日〜今回付与日前日（ただし最大1年間）」
// 入力: JSON { user_id:number, end_date:string('YYYY-MM-DD') }
// 出力: { user_id, start_date, end_date, work_days, total_minutes, total_hours, avg_hours_per_day }

session_start();
header('Content-Type: application/json');
require_once '../db_config.php';
require_once __DIR__ . '/_lib_worktime.php';

try {
  if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'not logged in']);
    exit;
  }
  // 管理者のみ
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
  $endDate = isset($j['end_date']) ? trim($j['end_date']) : '';
  $startDateOverride = isset($j['start_date']) ? trim($j['start_date']) : '';
  if ($userId <= 0 || !$endDate) {
    http_response_code(400);
    echo json_encode(['error' => 'user_id and end_date required']);
    exit;
  }

  // 前回付与日（end_date より前の最新の grant_date）を取得
  $stmt = $pdo->prepare('SELECT grant_date FROM paid_leaves WHERE user_id = ? AND grant_date < ? ORDER BY grant_date DESC, id DESC LIMIT 1');
  $stmt->execute([$userId, $endDate]);
  $prev = $stmt->fetch(PDO::FETCH_ASSOC);

  // 期間開始の決定:
  //  - 明示 start_date が指定された場合はそれを採用（検証: end 以前）
  //  - 無指定の場合:
  //      * 前回付与が存在: start = max(前回付与日, end-1年+1日)
  //      * 前回付与が無い: start = max(入社日, end-6ヶ月+1日) ただし入社日が不明なら end-6ヶ月+1日
  $end = new DateTime($endDate);
  $startByYear = clone $end;
  $startByYear->modify('-1 year');
  $startByYear->modify('+1 day');
  $startByHalf = clone $end;
  $startByHalf->modify('-6 months');
  $startByHalf->modify('+1 day');
  $start = $startByHalf; // 既定
  $startSource = 'auto';
  if ($startDateOverride) {
    $startTmp = DateTime::createFromFormat('Y-m-d', $startDateOverride);
    if (!$startTmp) {
      http_response_code(400);
      echo json_encode(['error' => 'invalid start_date format']);
      exit;
    }
    if ($startTmp > $end) {
      http_response_code(400);
      echo json_encode(['error' => 'start_date must be on or before end_date']);
      exit;
    }
    $start = $startTmp;
    $startSource = 'override';
  } else {
    if ($prev && isset($prev['grant_date']) && $prev['grant_date']) {
      $prevDt = new DateTime($prev['grant_date']);
      $start = ($prevDt > $startByYear) ? $prevDt : $startByYear;
      $startSource = 'prev_grant_or_1y';
    } else {
      // 入社日を考慮
      $stmt = $pdo->prepare('SELECT hire_date FROM user_detail WHERE user_id = ?');
      $stmt->execute([$userId]);
      $ud = $stmt->fetch(PDO::FETCH_ASSOC);
      if ($ud && !empty($ud['hire_date'])) {
        $hire = new DateTime($ud['hire_date']);
        $start = ($hire > $startByHalf) ? $hire : $startByHalf;
        $startSource = 'hire_or_6m';
      } else {
        $start = $startByHalf;
        $startSource = 'last_6m';
      }
    }
  }
  $startDate = $start->format('Y-m-d');

  // 実働分（休憩控除）を共通ライブラリで集計（有休は含めない、除外ステータス既定適用）
  $totalMinutes = compute_work_minutes($pdo, $userId, $startDate, $endDate, [
    'excludeStatuses' => ['off', 'ignore'],
    'includeHalfDay' => true,
    'workdayDefinition' => 'require_in_out',
  ]);

  // work_days は「打刻ありの日数」だが、共通関数は総分のみを返すため、従来どおりSQLでカウント
  $daysSql = "SELECT COUNT(*) AS work_days
              FROM timecards t
              WHERE t.user_id = :uid
                AND t.work_date BETWEEN :start AND :end
                AND t.clock_in IS NOT NULL AND t.clock_out IS NOT NULL";
  $ds = $pdo->prepare($daysSql);
  $ds->bindValue(':uid', $userId, PDO::PARAM_INT);
  $ds->bindValue(':start', $startDate, PDO::PARAM_STR);
  $ds->bindValue(':end', $endDate, PDO::PARAM_STR);
  $ds->execute();
  $workDays = (int)($ds->fetch(PDO::FETCH_ASSOC)['work_days'] ?? 0);
  $totalHours = round($totalMinutes / 60, 2);
  $avgHours = ($workDays > 0) ? round(($totalMinutes / 60) / $workDays, 2) : null;

  echo json_encode([
    'user_id' => $userId,
    'start_date' => $startDate,
    'end_date' => $endDate,
    'start_source' => $startSource,
    'work_days' => $workDays,
    'total_minutes' => $totalMinutes,
    'total_hours' => $totalHours,
    'avg_hours_per_day' => $avgHours,
  ]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error' => 'server error', 'message' => $e->getMessage()]);
}
