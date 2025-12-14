# [ATT-005] attendance_list リファクタ: `openNotifModal` の描画/既読処理分離

ステータス: Done
作成日: 2025-12-13
更新日: 2025-12-14
担当: 未割当
関連: `attendance_list.html`, `api/notifications_get.php`, `api/notifications_read.php`

## 背景 / 課題

- `openNotifModal()` がモーダルDOM生成、一覧描画、既読化のイベント付与、API呼び出しを一括で実施している。
- 通知は仕様が増えやすい領域で、現状のままだと変更時の影響範囲が大きい。

## 目的

- 「取得」「描画」「既読更新」を分離し、機能追加/修正の局所化を可能にする。

## スコープ

- フロントのみ: `attendance_list.html` の関数分割

## 非スコープ

- 通知のUX変更（フィルタ/ページング/検索の追加など）
- API仕様変更

## 提案アプローチ

- 分割案:
  - `fetchNotifications({onlyUnread})`
  - `renderNotificationsList(containerEl, items)`
  - `markNotificationRead(id)`
  - `createNotifModalShell()`
  - `bindNotifModalHandlers(modalEl)`

## 受け入れ基準（Acceptance Criteria）

- 通知モーダルが現状と同じ見た目・操作性で動作する。
- モーダルを開く/閉じる、既読化、バッジ更新が現状と同じ。

## 影響 / リスク

- 既読化のトリガーが複数ある場合に取りこぼしが起きやすい → 既読化は単一関数に統一。
