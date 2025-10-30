<?php
// api/admin_user_update.php
session_start();
header('Content-Type: application/json');
require_once '../db_config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'not logged in']);
    exit;
}
$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare('SELECT role FROM users WHERE id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user || $user['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'forbidden']);
    exit;
}
// POSTでユーザーID・新ユーザー名・権限を受け取る
$data = json_decode(file_get_contents('php://input'), true);
$targetId = $data['id'] ?? null;
$name = $data['name'] ?? null;
$role = $data['role'] ?? null;
$use_vehicle = array_key_exists('use_vehicle', $data) ? (int)$data['use_vehicle'] : null;
$has_contract_hours = array_key_exists('contract_hours_per_day', $data);
$contract_hours_per_day = $has_contract_hours ? $data['contract_hours_per_day'] : null; // null許容・未指定は現状維持
$has_hire_date = array_key_exists('hire_date', $data);
$hire_date_input = $has_hire_date ? $data['hire_date'] : null; // '' or 'YYYY-MM-DD' or null
// retire_date は任意（保存フォームからの編集にも対応）
$has_retire_date = array_key_exists('retire_date', $data);
$retire_date_input = $has_retire_date ? $data['retire_date'] : null;
// Stage B: scheduled fields (optional)
$has_sched_wd = array_key_exists('scheduled_weekly_days', $data);
$has_sched_wh = array_key_exists('scheduled_weekly_hours', $data);
$has_sched_ad = array_key_exists('scheduled_annual_days', $data);
$sched_wd_input = $has_sched_wd ? $data['scheduled_weekly_days'] : null;
$sched_wh_input = $has_sched_wh ? $data['scheduled_weekly_hours'] : null;
$sched_ad_input = $has_sched_ad ? $data['scheduled_annual_days'] : null;
// full_time flag (employment type): 1=常勤, 0=パート/その他。未指定は据置
$has_full_time = array_key_exists('full_time', $data);
$full_time_input = $has_full_time ? (int)$data['full_time'] : null;
if (!$targetId || !$name || !$role) {
    http_response_code(400);
    echo json_encode(['error' => 'id, name, role required']);
    exit;
}
// ユーザー名重複チェック（自分自身は除外）
$stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE name = ? AND id != ?');
$stmt->execute([$name, $targetId]);
if ($stmt->fetchColumn() > 0) {
    http_response_code(409);
    echo json_encode(['error' => 'duplicate name']);
    exit;
}
try {
    $pdo->beginTransaction();
    // users 側更新
    $stmt = $pdo->prepare('UPDATE users SET name = ?, role = ? WHERE id = ?');
    $stmt->execute([$name, $role, $targetId]);

    // user_detail 側 upsert（指定が無い場合は現状維持のため既存値を採用）
    if ($use_vehicle !== null || $contract_hours_per_day !== null || $has_hire_date || $has_retire_date || $has_sched_wd || $has_sched_wh || $has_sched_ad) {
        // 既存値取得
        $stmt = $pdo->prepare('SELECT use_vehicle, contract_hours_per_day, hire_date, retire_date, scheduled_weekly_days, scheduled_weekly_hours, scheduled_annual_days, full_time FROM user_detail WHERE user_id = ?');
        $stmt->execute([$targetId]);
        $detail = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $useV = ($use_vehicle !== null) ? $use_vehicle : (isset($detail['use_vehicle']) ? (int)$detail['use_vehicle'] : 1);
        // 契約時間: 明示指定がある場合は、'' または 0 を NULLとみなす。それ以外は現状維持。
        if ($has_contract_hours) {
            if ($contract_hours_per_day === '' || $contract_hours_per_day === null || (is_numeric($contract_hours_per_day) && (float)$contract_hours_per_day <= 0)) {
                $contractH = null;
            } else {
                $contractH = (float)$contract_hours_per_day;
            }
        } else {
            $contractH = isset($detail['contract_hours_per_day']) ? ((isset($detail['contract_hours_per_day']) && $detail['contract_hours_per_day'] !== null) ? (float)$detail['contract_hours_per_day'] : null) : null;
        }
        // hire_date は '' -> NULL として扱う
        if ($has_hire_date) {
            $hireDate = ($hire_date_input === '' || $hire_date_input === null) ? null : $hire_date_input;
        } else {
            $hireDate = isset($detail['hire_date']) ? $detail['hire_date'] : null;
        }
        // retire_date も '' -> NULL
        if ($has_retire_date) {
            $retireDate = ($retire_date_input === '' || $retire_date_input === null) ? null : $retire_date_input;
        } else {
            $retireDate = isset($detail['retire_date']) ? $detail['retire_date'] : null;
        }

        // scheduled fields: '' or null or <=0 => NULL; otherwise keep values; unspecified => keep existing
        if ($has_sched_wd) {
            if ($sched_wd_input === '' || $sched_wd_input === null || (is_numeric($sched_wd_input) && (int)$sched_wd_input <= 0)) {
                $schedWD = null;
            } else {
                $schedWD = (int)$sched_wd_input;
            }
        } else {
            $schedWD = isset($detail['scheduled_weekly_days']) ? (int)$detail['scheduled_weekly_days'] : null;
        }

        if ($has_sched_wh) {
            if ($sched_wh_input === '' || $sched_wh_input === null || (is_numeric($sched_wh_input) && (float)$sched_wh_input <= 0)) {
                $schedWH = null;
            } else {
                $schedWH = (float)$sched_wh_input;
            }
        } else {
            $schedWH = isset($detail['scheduled_weekly_hours']) ? (float)$detail['scheduled_weekly_hours'] : null;
        }

        if ($has_sched_ad) {
            if ($sched_ad_input === '' || $sched_ad_input === null || (is_numeric($sched_ad_input) && (int)$sched_ad_input <= 0)) {
                $schedAD = null;
            } else {
                $schedAD = (int)$sched_ad_input;
            }
        } else {
            $schedAD = isset($detail['scheduled_annual_days']) ? (int)$detail['scheduled_annual_days'] : null;
        }

        // full_time: 未指定は既存値、指定があれば 0/1 に正規化
        if ($has_full_time) {
            $fullTime = ($full_time_input === 1) ? 1 : 0;
        } else {
            $fullTime = isset($detail['full_time']) ? (int)$detail['full_time'] : 1;
        }

        $stmt = $pdo->prepare('INSERT INTO user_detail (user_id, use_vehicle, contract_hours_per_day, full_time, hire_date, retire_date, scheduled_weekly_days, scheduled_weekly_hours, scheduled_annual_days)
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                   ON DUPLICATE KEY UPDATE use_vehicle = VALUES(use_vehicle), contract_hours_per_day = VALUES(contract_hours_per_day), full_time = VALUES(full_time), hire_date = VALUES(hire_date), retire_date = VALUES(retire_date), scheduled_weekly_days = VALUES(scheduled_weekly_days), scheduled_weekly_hours = VALUES(scheduled_weekly_hours), scheduled_annual_days = VALUES(scheduled_annual_days)');
        $stmt->execute([$targetId, $useV, $contractH, $fullTime, $hireDate, $retireDate, $schedWD, $schedWH, $schedAD]);
    }
    $pdo->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'db error']);
}
