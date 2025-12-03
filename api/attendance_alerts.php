<?php
// api/attendance_alerts.php
// 管理者向け: 打刻アラートのサマリと一覧
session_start();
header('Content-Type: application/json');
require_once '../db_config.php';
require_once __DIR__ . '/_lib_worktime.php';

// 管理者チェック
if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(['error' => 'not logged in']);
  exit;
}
$adminId = (int)$_SESSION['user_id'];
$st = $pdo->prepare('SELECT role FROM users WHERE id = ?');
$st->execute([$adminId]);
$r = $st->fetch(PDO::FETCH_ASSOC);
if (!$r || $r['role'] !== 'admin') {
  http_response_code(403);
  echo json_encode(['error' => 'forbidden']);
  exit;
}

// パラメータ
$limit = isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : 50;
$days  = isset($_GET['days']) ? max(1, min(30, (int)$_GET['days'])) : 5; // 直近日数（365日営業想定）
$n     = isset($_GET['n']) ? max(1, min($days, (int)$_GET['n'])) : 3;    // 未打刻しきい値
// 追加: 未打刻の具体的な日付一覧を返すか
$wantMissingDates = isset($_GET['missing_dates']) && ($_GET['missing_dates'] === '1' || strtolower((string)$_GET['missing_dates']) === 'true');
// 表示対象のトグル（サーバ側フィルタ移行）
$includeRaw = isset($_GET['include']) ? trim((string)$_GET['include']) : '';
$includeSet = [];
if ($includeRaw !== '') {
  foreach (explode(',', $includeRaw) as $k) {
    $k = trim($k);
    if (in_array($k, ['working', 'past_incomplete', 'missing'], true)) {
      $includeSet[$k] = true;
    }
  }
}
// include未指定時は後方互換のため全て返す
if (!$includeSet) {
  $includeSet = [
    'working' => true,
    'past_incomplete' => true,
    'missing' => true,
  ];
}

try {
  // アラートから除外する日（全休・無視）。半休は除外しない。
  // ライブラリ既定に合わせ 'off','ignore' を採用（ビュー側で off_full→off に正規化前提）
  $excludeStatuses = "('off','ignore')";

  // 1) 今日 勤務中（clock_inあり・clock_outなし）＝勤務中
  $todayNoOut = [];
  if (!empty($includeSet['working'])) {
    $sqlToday = "SELECT u.id AS user_id, u.name, t.work_date, t.clock_in
                                         FROM timecards t
                                         JOIN users u ON u.id = t.user_id
                                         LEFT JOIN day_status_effective o
                                             ON o.user_id = u.id AND o.date = t.work_date AND o.status IN $excludeStatuses
                                         WHERE t.work_date = CURDATE() AND t.clock_in IS NOT NULL AND t.clock_out IS NULL
                                             AND o.user_id IS NULL
                                         ORDER BY t.clock_in ASC
                                         LIMIT " . (int)$limit;
    $st1 = $pdo->prepare($sqlToday);
    $st1->execute();
    $todayNoOut = $st1->fetchAll(PDO::FETCH_ASSOC);
  }

  // 2) 過去日の未退勤（直近$days日・当日除外）
  $pastIncomplete = [];
  if (!empty($includeSet['past_incomplete'])) {
    $constDays2 = (int)$days;
    $sql2 = "SELECT u.id AS user_id, u.name, t.work_date, t.clock_in
                                    FROM timecards t
                                    JOIN users u ON u.id = t.user_id
                                    LEFT JOIN day_status_effective o
                                        ON o.user_id = u.id AND o.date = t.work_date AND o.status IN $excludeStatuses
                                    WHERE t.work_date < CURDATE()
                                        AND t.work_date >= DATE_SUB(CURDATE(), INTERVAL $constDays2 DAY)
                                        AND t.clock_in IS NOT NULL AND t.clock_out IS NULL
                                        AND o.user_id IS NULL
                                    ORDER BY t.work_date DESC, u.id ASC
                                    LIMIT " . (int)$limit;
    $st2 = $pdo->prepare($sql2);
    $st2->execute();
    $pastIncomplete = $st2->fetchAll(PDO::FETCH_ASSOC);
  }

  // 3) 直近 $days 日（今日除く）での未打刻（365日営業前提: 曜日判定なし）
  $totalDays = (int)$days;
  $rangeStartDt = (new DateTime('today'))->modify('-' . $totalDays . ' day');
  $rangeEndDt = new DateTime('yesterday');
  $rangeStart = $rangeStartDt->format('Y-m-d');
  $rangeEnd = $rangeEndDt->format('Y-m-d');

  $missingUsers = [];
  if (!empty($includeSet['missing']) && $totalDays > 0) {
    // 定数はSQL文字列に直接埋め込み（サニタイズ済み整数）
    $constDays = (int)$totalDays;
    // 期間内の「全休/無視」日数をユーザー単位で減算してから不足日数を算出（午前/午後休は減算しない）
    $sql3 = "SELECT u.id AS user_id,
                        u.name,
                        COUNT(DISTINCT t.work_date) AS present,
                                                ($constDays - IFNULL(ov.ov_days, 0) - COUNT(DISTINCT t.work_date)) AS missing,
                        DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL $constDays DAY), '%Y-%m-%d') AS range_start,
                        DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 DAY), '%Y-%m-%d') AS range_end,
                        IFNULL(ov.ov_days, 0) AS override_days,
                        ($constDays - IFNULL(ov.ov_days, 0)) AS effective_days
                 FROM users u
                 LEFT JOIN (
                    SELECT user_id, COUNT(*) AS ov_days
                                        FROM day_status_effective
                                                                                WHERE status IN $excludeStatuses
                      AND date BETWEEN DATE_SUB(CURDATE(), INTERVAL $constDays DAY) AND DATE_SUB(CURDATE(), INTERVAL 1 DAY)
                    GROUP BY user_id
                 ) ov ON ov.user_id = u.id
                 LEFT JOIN timecards t
                   ON t.user_id = u.id
                  AND t.work_date BETWEEN DATE_SUB(CURDATE(), INTERVAL $constDays DAY) AND DATE_SUB(CURDATE(), INTERVAL 1 DAY)
                 GROUP BY u.id, u.name, ov.ov_days
                 HAVING missing >= ? AND effective_days > 0
                 ORDER BY missing DESC, u.id ASC
                 LIMIT " . (int)$limit;
    $st3 = $pdo->prepare($sql3);
    $st3->execute([$n]);
    while ($row = $st3->fetch(PDO::FETCH_ASSOC)) {
      $row['missing_days'] = max(0, (int)$row['missing']);
      $missingUsers[] = $row;
    }

    // オプション: 各ユーザーの未打刻日付リストを構築
    if ($wantMissingDates && $missingUsers) {
      // 期間内の出勤日（present）を一括取得
      $presentMap = [];
      $sqlP = "SELECT user_id, work_date FROM timecards WHERE work_date BETWEEN ? AND ?";
      $stP = $pdo->prepare($sqlP);
      $stP->execute([$rangeStart, $rangeEnd]);
      while ($r = $stP->fetch(PDO::FETCH_ASSOC)) {
        $uid = (int)$r['user_id'];
        $d = $r['work_date'];
        if (!isset($presentMap[$uid])) $presentMap[$uid] = [];
        $presentMap[$uid][$d] = true;
      }
      // 期間内の除外日（全休/無視）を一括取得（effective）
      $excludeMap = [];
      $sqlO = "SELECT user_id, date FROM day_status_effective WHERE status IN $excludeStatuses AND date BETWEEN ? AND ?";
      $stO = $pdo->prepare($sqlO);
      $stO->execute([$rangeStart, $rangeEnd]);
      while ($r = $stO->fetch(PDO::FETCH_ASSOC)) {
        $uid = (int)$r['user_id'];
        $d = $r['date'];
        if (!isset($excludeMap[$uid])) $excludeMap[$uid] = [];
        $excludeMap[$uid][$d] = true;
      }
      // 範囲内日付の配列（昇順）
      $dates = [];
      $cursor = new DateTime($rangeStart);
      $endDt = new DateTime($rangeEnd);
      while ($cursor <= $endDt) {
        $dates[] = $cursor->format('Y-m-d');
        $cursor->modify('+1 day');
      }
      // 各 missingUsers に missing_dates を付与（降順で使いやすく）
      foreach ($missingUsers as &$mu) {
        $uid = (int)$mu['user_id'];
        $arr = [];
        foreach ($dates as $d) {
          if (!empty($presentMap[$uid][$d])) continue; // 出勤あり
          if (!empty($excludeMap[$uid][$d])) continue; // 全休/無視
          $arr[] = $d;
        }
        // UI では最近日から処理したいので降順に
        rsort($arr);
        $mu['missing_dates'] = $arr;
      }
      unset($mu);
    }
  }

  echo json_encode([
    'ok' => true,
    'summaries' => [
      'today_no_clock_out_count' => count($todayNoOut),
      'past_incomplete_count' => count($pastIncomplete),
      'recent_missing_users_count' => count($missingUsers)
    ],
    'items' => [
      // 勤務中（today_no_clock_out）は従来キー名を維持
      'today_no_clock_out' => $todayNoOut,
      'past_incomplete' => $pastIncomplete,
      'recent_missing_users' => $missingUsers,
      'workday_range' => ['days' => $days, 'range_start' => $rangeStart, 'range_end' => $rangeEnd]
    ]
  ]);
} catch (Throwable $e) {
  http_response_code(500);
  // 管理者専用API: 例外をサーバーログへ記録（メッセージのみ）
  error_log('api/attendance_alerts.php error: ' . $e->getMessage());
  echo json_encode(['error' => 'db error']);
}
