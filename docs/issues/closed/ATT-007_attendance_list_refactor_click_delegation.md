# [ATT-007] attendance_list リファクタ: クリック委譲ハンドラの分割（分類/クイック操作）

ステータス: Done
作成日: 2025-12-13
更新日: 2025-12-14
担当: 未割当
関連: `attendance_list.html`, `api/day_status_self_set.php`, `api/day_status_self_clear.php`

## 背景 / 課題

- `document.addEventListener('click', async (e) => { ... })` が
  - 分類ボタン（cls-btn）
  - クリアボタン
  - 欠落行の全休クイックボタン（mini-off）
  を単一ハンドラで処理しており、分岐が増えるほど見通しが悪くなる。

## 目的

- ハンドラを「用途別」に分割し、分岐の増殖と回帰バグを抑える。

## スコープ

- フロントのみ: `attendance_list.html`
- イベント委譲自体は維持（登録は1箇所でもよい）

## 非スコープ

- ボタン追加/削除などのUX変更

## 提案アプローチ

- 分割案:
  - `handleClassificationButtonClick(e) => boolean`
  - `handleClassificationClearClick(e) => boolean`
  - `handleMiniOffClick(e) => boolean`
  - 既存の click listener 内で順に呼んで、trueならreturn。

## 受け入れ基準（Acceptance Criteria）

- 分類変更/クリア/クイック全休が現状と同じ挙動。
- click listener の本体が短くなり、分岐が用途別関数に移っている。

## 影響 / リスク

- `stopPropagation` の位置が変わると行クリック等に影響 → 既存と同じタイミングで呼ぶ。
