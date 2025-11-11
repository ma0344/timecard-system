# 勤怠修正申請機能の新規実装

## 目的

管理者による上書き作業を減らし、整合性・監査性を高める。

## 要件

- `attendance_list.html` の各日から「修正申請」を起動（当日以外）
- 入力項目：日付、出勤/退勤、休憩（0..n）、理由、補足メモ
- 本人申請 → 管理者承認/却下 → 実績へ反映（監査ログ）

## 受入基準

- UI/サーバーでの時系列・重複/交差チェック
- 二重申請防止、未来日不可、RateLimit、権限境界の遵守

## API/DB 案

- DB: `attendance_corrections` (id, user_id, date, clock_in, clock_out, breaks(json), reason, status(pending/approved/rejected), approver_user_id, decided_at, created_at)
- POST `api/attendance_corrections_create.php`
- GET `api/attendance_corrections_list.php?status=pending|mine`
- POST `api/attendance_corrections_decide_admin.php`（承認/却下）

## 備考

- 目的・要件・受入基準・API/DB 案は docs/ROADMAP_USER_UI.md『B. 勤怠修正申請』に準拠
- 実装時はセキュリティ・権限・パフォーマンス要件も考慮すること
