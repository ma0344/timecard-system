# ALERT-001: アラート API を day_status_effective 参照へ切替

ステータス: Closed
作成日: 2025-11-11
担当: 未割当
関連: sql/migrations/2025-11-11_day_status_effective_view.sql, api/my_alerts.php, api/attendance_alerts.php

## 背景

- これまでアラート算出の休日/除外判定は `day_status_overrides` 依存だった。
- ベースライン（`day_status`）と上書き（`day_status_overrides`）の使い分けを確立し、参照は `day_status_effective` ビューに統一する。

## 目的

- 休日（off）と無視（ignore）を一貫して除外。半休（am_off/pm_off）は除外しない方針を API に反映。
- ベースラインが存在する/しないに関わらず、単一の参照口で正しい判定を行う。

## スコープ

- `api/my_alerts.php` の未打刻除外日取得を `day_status_effective` 参照に変更。
- `api/attendance_alerts.php` での LEFT JOIN/集計/欠落日列挙のいずれも `day_status_effective` に切替。
- 除外条件は `status IN ('off','ignore')` に統一（半休は対象）。

## 変更点（実装済）

- my_alerts: overrides → effective へ切替。除外集合を ('off','ignore') に更新。
- attendance_alerts: overrides 参照を全面的に effective へ切替。
  - 今日の未退勤/過去日の未退勤の JOIN を差し替え。
  - 未打刻集計の除外日カウントをサブクエリで effective 参照へ。
  - missing_dates 構築の除外日取得を effective 参照へ。

## 受け入れ基準

- 休日/無視の日は「未打刻」のカウントや行生成に含まれない。
- 半休は除外されず、未打刻対象として扱われる。
- ベースラインが未登録でも、overrides のみで従来同等に機能する（ビューが最新 override を返す）。

## 検証観点

- 直近 N 日の missing_users における effective_days と missing の整合。
- 既存の `limit`, `days`, `n`, `missing_dates` パラメータの挙動が変わらないこと。

## クローズメモ（2025-11-14）

- ビュー適用と両画面の確認完了。`status IN ('off','ignore')` の除外が未打刻算出/missing_dates と整合。
- 以後の拡張は運用チューニング（days/limit）や性能面の最適化に委譲。
