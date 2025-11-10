# Timecard System

PHP + MySQL ベースの勤怠・有給管理システムです。モバイル最適化されたユーザー向け「My Day」ダッシュボード、打刻、有給申請、通知、管理機能を提供します。

## 構成（主要ファイル/ディレクトリ）

- ルート HTML/エントリ
  - `index.php`（ルートインデックス）
  - `login.html` / `register.html` / `reset_password.html`
  - `punch.html`（打刻）
  - `attendance_list.html`（勤務・有給 管理）
  - `myday_dashboard.html`（一般ユーザー向けダッシュボード）
  - 管理系: `admin.html`, `admin_users.html`, `admin_request.html`, ほか
- API（PHP）: `api/` 配下
  - 打刻: `clock_in.php`, `clock_out.php`, `break_start.php`, `break_end.php`
  - 通知: `notifications_get.php`, `notifications_read.php`
  - アラート/サマリ: `my_alerts.php`, `today_events.php`, `today_status_summary.php`
  - 有給・設定: `paid_leave_*.php`, `settings_*.php`, `smtp_*.php`, ほか
- 共通スタイル: `css/common.css`
- ドキュメント: `docs/`（ロードマップ、課題、設計メモ）
- マイグレーション: `sql/migrations/`
- DB 接続設定: `db_config.php`

## 主な機能

- My Day（個人ダッシュボード）
  - 今日のステータス帯（出勤/休憩/退勤）とクイックアクション
  - 本人向けアラート（未退勤・未打刻）ピル＋日付一覧モーダル
  - 今日のタイムライン（新しい順・折りたたみ）
  - 今日のメモ（端末自動保存、退勤時にサーバー保存）
  - 通知未読バッジ＋最新 3 件プレビュー（一行表示・未読優先）
- 打刻・休憩の記録、勤務一覧と有給申請
- 通知（アプリ内）と SMTP 連携（設定/テスト）
- 管理ダッシュボード（期限間近/未承認/打刻アラート 等）
- 監査・レート制限（基礎対応）

## 前提ソフトウェア

- PHP 8.0+（mbstring, pdo_mysql 推奨）
- MySQL 8.0+（互換モードでも可）
- Web サーバ（Apache/Nginx など）または PHP 内蔵サーバ

## セットアップ

1. DB 準備

- MySQL にデータベースを作成
- `sql/migrations/` のスクリプトを古い順（ファイル名順）に適用

2. 接続設定

- `db_config.php` を編集し、ホスト/DB 名/ユーザー/パスワードを環境に合わせて設定

3. 配置

- 本ディレクトリを Web サーバのドキュメントルート（もしくは仮想ホスト）に配置

4. ローカル開発（任意）

```bash
# PHP 内蔵サーバ例（ポートは任意）
php -S localhost:8000 -t .
```

## 使い方

- ブラウザで `login.html` からログイン
- 一般ユーザーは `myday_dashboard.html` を起点に日々の操作が可能
- 管理者は `admin.html` から各種管理画面へ

## ドキュメント

- 全体ロードマップ: `docs/ROADMAP.md`
- 一般ユーザー向け UI/機能ロードマップ: `docs/ROADMAP_USER_UI.md`
- 課題・仕様: `docs/issues/`

## セキュリティ/運用メモ（抜粋）

- 主要 API にレート制限（IP× エンドポイント）
- 申請・決裁の監査ログ（決裁者 ID, IP/UA, トークンハッシュ保存 等）
- メール/SMTP 設定は管理画面から編集・テスト可

※ 本 README は随時アップデートします。詳細や進行中の計画は `docs/` を参照してください。
