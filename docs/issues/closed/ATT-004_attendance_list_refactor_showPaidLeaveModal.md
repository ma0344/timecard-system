# [ATT-004] attendance_list リファクタ: `showPaidLeaveModal` のUI生成/送信分離

ステータス: Done
作成日: 2025-12-13
更新日: 2025-12-14
担当: 未割当
関連: `attendance_list.html`, `api/leave_requests_create.php`

## 背景 / 課題

- `showPaidLeaveModal()` が以下を1関数で実施している。
  - モーダルDOMの生成（大きいテンプレ文字列）
  - 初期値計算（日付/時間）
  - 入力バリデーション
  - API送信と成功/失敗時のUI更新
- 今後、申請系UIが増えると同パターンの重複が増える。

## 目的

- UI生成と送信ロジックを分離し、再利用可能な形にする。
- バリデーション/送信のテスト容易性を上げる（手動でも追いやすい）。

## スコープ

- フロントのみ: `attendance_list.html` の関数分割

## 非スコープ

- モーダルのデザイン変更、入力項目追加
- API/DB変更

## 提案アプローチ

- 分割案:
  - `createModalBase({id, innerHTML}) => modalEl`
  - `buildPaidLeaveModalHTML({defaultDate, defaultHours}) => string`
  - `readPaidLeaveForm(modalEl) => {used_date, hours, reason}`
  - `validatePaidLeaveForm(values) => errorMessage|null`
  - `submitPaidLeave(values)`（fetch）
  - `bindPaidLeaveModalHandlers(modalEl)`
- 初期日付は既存の `getInitialAddDate(monthVal)` を利用。

## 受け入れ基準（Acceptance Criteria）

- 表示・入力・送信・エラー表示の挙動が現状と同じ。
- `showPaidLeaveModal` は「作る→bind→表示」の短い関数になっている。

## 実装ステップ

1. HTML生成を `buildPaidLeaveModalHTML` に抽出
2. 送信処理を `submitPaidLeave` に抽出
3. `showPaidLeaveModal` を組み立て関数へ

## 影響 / リスク

- DOM要素のid参照が多いため、抽出時に参照漏れが起きやすい → `modalEl.querySelector` へ寄せる。
