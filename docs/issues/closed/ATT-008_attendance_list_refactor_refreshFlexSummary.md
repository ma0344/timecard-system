# [ATT-008] attendance_list リファクタ: `refreshFlexSummary` の取得/描画分離

ステータス: Done
作成日: 2025-12-13
更新日: 2025-12-14
担当: 未割当
関連: `attendance_list.html`, `api/flex_summary.php`

## 背景 / 課題

- `refreshFlexSummary(monthVal)` が
  - API呼び出し
  - トークンによる競合防止
  - 表示/非表示条件判定
  - DOM更新（複数要素）
  - 2か月集計の分岐
  をまとめて行っている。

## 目的

- 取得ロジックと描画ロジックを分け、仕様追加や不具合修正時の影響範囲を小さくする。

## スコープ

- フロントのみ: `attendance_list.html`

## 非スコープ

- フレックス表示仕様の変更
- API変更

## 提案アプローチ

- 分割案:
  - `fetchFlexSummary(monthVal) => data`
  - `shouldShowFlexSummary(data) => boolean`
  - `renderFlexSummary(data)` / `renderFlexTwoMonth(data)`
- 既存の request token による「古い応答を捨てる」挙動は維持。

## 受け入れ基準（Acceptance Criteria）

- 表示条件（フルタイム/精算月/2か月表示）が現状と同じ。
- DOM更新がレンダ関数に集約され、`refreshFlexSummary` の本体が短い。

## 影響 / リスク

- 例外時の非表示・初期化（twoMonth行など）が漏れると表示崩れ → 例外時のUIリセット関数を用意。
