# [ATT-006] attendance_list リファクタ: `connectSSE` の接続管理/イベント処理分離

ステータス: Done
作成日: 2025-12-13
更新日: 2025-12-14
担当: 未割当
関連: `attendance_list.html`, `api/events_stream.php`

## 背景 / 課題

- `connectSSE()` が以下を内包している。
  - 可視状態（`document.hidden`）による接続抑制
  - 既存接続のclose
  - `EventSource`作成とイベントハンドラ登録
  - endイベント時の再接続
- イベント種別追加時に `connectSSE` が肥大化しやすい。

## 目的

- SSE接続管理（作成/close/reconnect）と、イベントごとの処理（refresh等）を分離して見通しを上げる。

## スコープ

- フロントのみ: `attendance_list.html` の関数分割

## 非スコープ

- SSEプロトコルやAPIの変更
- 新しいイベントの追加

## 提案アプローチ

- 分割案:
  - `createEventSource() => EventSource`
  - `bindSseHandlers(es)`（heartbeat/leave_request_decided/paid_leave_updated/end）
  - `scheduleReconnect()`
  - `connectSSE()` は「作る→bind→保持」へ縮小

## 受け入れ基準（Acceptance Criteria）

- 可視時のみ接続、非可視でclose、可視復帰で再接続の挙動が同じ。
- 承認反映の再取得（flex/paidLeave）が現状と同じ。

## 影響 / リスク

- 再接続時にイベントリスナーが多重登録されないこと（毎回新しいEventSourceにbindする）を保証する。
