<?php
// api/paid_leave_expiring_report.php
// CSV エクスポート: 有休失効間近レポート
session_start();
header('Content-Type: text/csv; charset=Shift_JIS');
require_once '../db_config.php';

// 認証・管理者チェック
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo mb_convert_encoding('エラー: ログインが必要です', 'SJIS', 'UTF-8');
    exit;
}

$adminId = $_SESSION['user_id'];
$stmt = $pdo->prepare('SELECT role FROM users WHERE id = ?');
$stmt->execute([$adminId]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$admin || $admin['role'] !== 'admin') {
    http_response_code(403);
    echo mb_convert_encoding('エラー: 管理者権限が必要です', 'SJIS', 'UTF-8');
    exit;
}

// パラメータ取得（デフォルト: 30日以内）
$within = isset($_GET['within']) ? (int)$_GET['within'] : 30;
if ($within < 1 || $within > 365) {
    $within = 30;
}

try {
    // 有休 失効間近のユーザーを取得（dashboard_summary.phpと同じロジック）
    $sql = "
        SELECT u.id AS user_id, u.name, s.balance_hours, s.next_expire_date,
               DATEDIFF(s.next_expire_date, CURDATE()) AS days_left
        FROM users u
        JOIN user_leave_summary s ON s.user_id = u.id
        LEFT JOIN user_detail d ON d.user_id = u.id
        WHERE s.next_expire_date IS NOT NULL
          AND s.next_expire_date > CURDATE()
          AND s.next_expire_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
          AND s.balance_hours > 0
          AND (d.retire_date IS NULL OR d.retire_date > CURDATE())
        ORDER BY s.next_expire_date ASC, u.id ASC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$within]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ファイル名（英数字のみ、日本語を含めない）
    $filename = 'paid_leave_expiring_' . date('Y-m-d') . '.csv';
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    // 出力バッファ
    $output = fopen('php://output', 'w');

    // ヘッダー行（UTF-8で準備してからShift-JISに変換）
    $header = ['ユーザーID', '氏名', '残高時間', '次回失効日', '残日数'];
    $headerSjis = array_map(function($col) {
        return mb_convert_encoding($col, 'SJIS', 'UTF-8');
    }, $header);
    fputcsv($output, $headerSjis);

    // データ行
    foreach ($rows as $row) {
        $line = [
            $row['user_id'],
            mb_convert_encoding($row['name'], 'SJIS', 'UTF-8'),
            number_format((float)$row['balance_hours'], 2),
            $row['next_expire_date'],
            $row['days_left']
        ];
        fputcsv($output, $line);
    }

    fclose($output);
} catch (Exception $e) {
    http_response_code(500);
    echo mb_convert_encoding('エラー: データベースエラー', 'SJIS', 'UTF-8');
}
