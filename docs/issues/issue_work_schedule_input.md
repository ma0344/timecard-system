# 一般職員による勤務予定入力・作成機能

## 目的

一般職員が自分の勤務予定（出勤・退勤・休暇等）を事前に入力・申告できるようにする

## 要件

- 勤務予定の新規作成・編集・削除（自分の分のみ）
- 入力項目：日付、出勤時刻、退勤時刻、休憩、備考
- 予定は管理者も参照可能

## 受入基準

- 本人・管理者双方のダッシュボードで予定が反映される
- 予定と実績の差分が明確に分かる

## API/DB 案

- DB: `work_schedules` (id, user_id, date, clock_in, clock_out, breaks(json), note, created_at, updated_at)
- POST/PUT/DELETE `api/work_schedules.php`
- GET `api/work_schedules.php?user_id=xxx&date=yyy`

## 備考

- 既存の勤怠・有給申請機能との連携・整合性に注意
- 実装時は権限・バリデーション・UI/UX も考慮すること
