# [ATT-010] attendance_list リファクタ: UI生成の「データ生成」と「HTML生成」の分離（横断）

ステータス: Proposed
作成日: 2025-12-14
担当: 未割当
関連: `attendance_list.html`

## 背景 / 課題

- `attendance_list.html` の各UI（インラインパネル、モーダル、カード等）で、以下が1つの関数に混在しやすい。
  - 表示に必要なデータの収集・整形（global state 参照、計算、escape 等）
  - HTML文字列の組み立て
  - DOM挿入とイベント配線
- 「データの変更」「表示の変更」「イベントの変更」が絡み合い、差分が読みづらく回帰リスクが上がる。

## 目的

- UI生成処理を原則として以下に分割し、見通しと安全な変更性を上げる。
  1) `*BuildModel(...)`: 表示に必要なデータ（model）を生成するだけ
  2) `*BuildHTML(model)`: model からHTML文字列を生成するだけ
  3) `render*` / `open*` / `insert*`: DOM挿入とイベント配線（必要最小）

## 現状（前提）

- インラインパネル周りは一部、上記思想で分割が進んでいる（`inlinePanelRender*` の簡易モード、`work`/`add` の一部）。
- 本Issueは横断的に同思想へ統一するための「設計・移行」Issue。

## スコープ

- フロントのみ: `attendance_list.html` の関数分割・命名整理
- 既存UI/UX・API呼び出し・レスポンスは維持（仕様変更なし）

## 非スコープ

- UI/UXの追加・変更（表示項目追加、文言変更、ボタン追加等）
- CSS/デザインの刷新
- API変更、レスポンス形式変更

## 命名・構造ガイド（案）

- `XxxBuildModel(...)` は「純粋関数に近く」する（DOM参照や副作用を避ける）
- `XxxBuildHTML(model)` は「文字列生成のみ」（fetch/DOMアクセスしない）
- `renderXxx(ctx)` は「model→HTML→イベント配線」に限定する
- Escape は model 側で済ませるか、HTML側で一箇所に集約する（混在を避ける）

## 対象候補（例）

- 既に着手済み: インラインパネル `paid/ignore/locked/off/unknown/work/add`
- 後続候補:
  - 有給モーダル / 通知モーダル
  - サマリ/カード類（flex等）
  - 一覧行の描画（record row）

## 受け入れ基準（Acceptance Criteria）

- 対象UIについて、renderer内に「データ整形」と「HTML組み立て」が混在しない。
- 表示・操作・保存・キャンセル等の挙動が既存と同じ。
- `BuildModel/BuildHTML` が単体で読みやすく、差分レビューで影響範囲が追いやすい。

## 実装ステップ（案）

1. 対象UIを列挙し、優先度を付ける（影響の小さいものから）
2. 1UIずつ `BuildModel/BuildHTML` に分割し、rendererを薄くする
3. 手動テスト（既存フロー：開閉、保存、期間切替、フィルタ、SSE反映）

## 影響 / リスク

- 分割時に参照順序や副作用が変わると回帰しやすい → まず「移動中心」で進める
- `attendance_list.html` はグローバル依存が多い → model生成関数の引数/戻りを明確にして事故を減らす
