# [ATT-002] attendance_list リファクタ: `insertInlinePanelFor` のモード分岐分割

ステータス: Done
作成日: 2025-12-13
更新日: 2025-12-14
担当: 未割当
関連: `attendance_list.html`

## 背景 / 課題

- `attendance_list.html` の `insertInlinePanelFor(anchorLi, date, mode, rec)` が巨大化し、以下の責務が単一関数に混在している。
  - UIの骨格生成（`li`/`panel`）
  - `mode`別のHTML生成（paid/ignore/add/work/locked/off…）
  - 入力バリデーション、ボタンのenable/disable
  - API呼び出しと成功時の再取得（`loadRecordsByPeriod` など）
  - オーバーレイ/閉じる処理/戻り導線
- 変更時に「他のmodeに副作用がないか」の確認コストが高い。

## 目的

- `mode`ごとのレンダリング・イベント配線を分割し、変更容易性を上げる。
- 共通処理（close、overlay、スクロール合わせ、autoBack等）を一箇所に集約する。

## スコープ

- フロントのみ: `attendance_list.html`
- 関数分割・命名整理・共通ヘルパ抽出（機能追加はしない）

## 非スコープ

- UI/UXの仕様変更（新しいボタン追加、表示変更、動線変更）
- API仕様変更

## 提案アプローチ

- `insertInlinePanelFor` を「共通初期化 + renderer呼び出し」に縮小する。
  - 共通: `createPanelShell(date)`, `bindClose(panel, closePanel)`, `applyEditingMode()`, `insertActiveRowAfter`, `alignActiveRowToListLabel`
  - mode別: `panelRenderers = { add(ctx), work(ctx), off(ctx), paid(ctx), ignore(ctx), locked(ctx) }`
- ctxに必要な値だけ渡す（DOM要素、date、rec、lockedフラグ、close関数、必要なヘルパ）
- Option B（再取得→再オープン）相当の処理が複数modeにある場合は `reopenInlinePanelAfterReload({date, mode})` へ集約。

## 受け入れ基準（Acceptance Criteria）

- `insertInlinePanelFor` が「共通処理 + modeディスパッチ」に整理され、各modeの処理が独立した関数になっている。
- 既存のmode（paid/ignore/add/work/locked/off）で表示・保存・キャンセル・閉じるが現状と同じ。
- 既存のスクロール合わせ・overlay・autoBack の挙動が変わらない。

## 実装ステップ

1. `createPanelShell(date)` を抽出（DOM生成の共通化）
2. `panelRenderers` を作り、最小のmode（例: ignore）から移植
3. `add`/`work` など大きいmodeを段階移植
4. `insertInlinePanelFor` の分岐を削減し、最終的に renderer 呼び出しのみへ
5. 手動テスト（各modeの開閉/保存/キャンセル）

## 影響 / リスク

- 分割時に close/overlay の結線漏れで「閉じない」系の不具合が出やすい → 共通ヘルパで統一する。
- `add` mode は入力項目が多く、移植時にDOM id参照の取り違いが起きやすい → ctx内に要素参照をまとめる。
