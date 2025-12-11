<?php
/*
 * 目的: ダッシュボード用の集計情報（ユーザー数、失効間近有給など）を返します。
 * 入力: なし（管理者権限で内容追加）
 * 出力: カード表示向けのサマリJSON
 */
?>
<?php
// api/dashboard_summary.php
// 管理ダッシュボード用の要約データを返す
session_start();
header('Content-Type: application/json');
require_once '../db_config.php';

// 認証・管理者チェック
if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(['error' => 'not logged in']);
  exit;
}

$adminId = $_SESSION['user_id'];
$stmt = $pdo->prepare('SELECT role FROM users WHERE id = ?');
$stmt->execute([$adminId]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$admin || $admin['role'] !== 'admin') {
  http_response_code(403);
  echo json_encode(['error' => 'forbidden']);
  exit;
}

try {
  // 全ユーザー数
  $totalUsers = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();

  // アクティブユーザー数（退職日が未設定 or 本日より後）
  $stmt = $pdo->query("SELECT COUNT(*) FROM users u LEFT JOIN user_detail d ON d.user_id = u.id WHERE d.retire_date IS NULL OR d.retire_date > CURDATE()");
  $activeUsers = (int)$stmt->fetchColumn();

  // 有休 失効間近（30日以内、残 > 0、退職済みは除外）
  $sql = "
        SELECT u.id AS user_id, u.name, s.balance_hours, s.next_expire_date,
               DATEDIFF(s.next_expire_date, CURDATE()) AS days_left
        FROM users u
        JOIN user_leave_summary s ON s.user_id = u.id
        LEFT JOIN user_detail d ON d.user_id = u.id
        WHERE s.next_expire_date IS NOT NULL
          AND s.next_expire_date > CURDATE()
          AND s.next_expire_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
          AND s.balance_hours > 0
          AND (d.retire_date IS NULL OR d.retire_date > CURDATE())
        ORDER BY s.next_expire_date ASC, u.id ASC
        LIMIT 50
    ";
  $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
  $expiringCount = count($rows);

  echo json_encode([
    'ok' => true,
    'totals' => [
      'users_total' => $totalUsers,
      'users_active' => $activeUsers,
    ],
    'expiring_30d' => [
      'count' => $expiringCount,
      'top' => array_map(function ($r) {
        return [
          'user_id' => (int)$r['user_id'],
          'name' => $r['name'],
          'balance_hours' => isset($r['balance_hours']) ? (float)$r['balance_hours'] : 0.0,
          'next_expire_date' => $r['next_expire_date'],
          'days_left' => (int)$r['days_left'],
        ];
      }, $rows)
    ]
  ]);
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(['error' => 'db error']);
}
