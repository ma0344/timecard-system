# VS Code ワークスペースを NAS 直開きからローカル同期へ移行する手順（Copilot の会話履歴を保持）

このドキュメントは、UNC/NAS 上のプロジェクトをローカルへ移し、Syncthing などで複数 PC 間を同期しながら、VS Code の Copilot チャット履歴（workspaceStorage）を可能な限り引き継ぐための実践手順です。

対象環境の一例（実績あり）:

- OS: Windows 10/11
- エディタ: VS Code（日本語表示）
- 同期: Syncthing
- 旧パス（NAS）: `\\MA-NAS\web\timecard\timecard-system`
- 新パス（ローカル）: `C:\Users\ma\Desktop\timecard-system`

---

## 背景とポイント

- NAS 直開きではファイルウォッチャーが `ECONNRESET` などで不安定になりがちです。ローカル化で解消するケースが多いです。
- VS Code は「ワークスペースの絶対パス」に基づいて `workspaceStorage` の ID を生成します。単純にフォルダ位置を変えると新しい ID になり、Copilot の会話などが消えたように見えます。
- 本手順は「新旧 ID を安全に橋渡し」してチャット履歴を復元する実践手順をまとめています。

---

## 推奨手順（橋渡し法）

ユーザー実績ベースで最もスムーズだった方法です。VS Code の多ルート・ワークスペースを使って旧 → 新へ段階的に切り替えます。

1. NAS からローカルへディレクトリをコピー

   - 例: `\\MA-NAS\web\timecard\timecard-system` → `C:\Users\ma\Desktop\timecard-system`
   - PowerShell（管理者権限推奨）例:
     ```powershell
     mkdir "$env:USERPROFILE\Desktop\timecard-system" -ea SilentlyContinue
     robocopy "\\MA-NAS\web\timecard\timecard-system" "$env:USERPROFILE\Desktop\timecard-system" /E /COPY:DAT /DCOPY:T /R:2 /W:2 /MT:16 /XJ
     ```

2. NAS 上のワークスペースを VS Code で開き、メニュー「ファイル」→「名前を付けてワークスペースを保存…」で、ローカルの `timecard-system` 内に保存（例: `timecard-system.code-workspace`）

3. VS Code を終了

4. いま保存したローカルのワークスペース（`.code-workspace`）を一度開いて閉じる

   - 目的: 新しいローカルパスに紐づく `workspaceStorage` の“新 ID”フォルダを生成させる（この時点では Copilot チャットはまだ復活しません）

5. 旧 ID の `workspaceStorage` 内容を、新 ID へまるごとコピー

   - フォルダ場所: `%APPDATA%\Code\User\workspaceStorage`
   - 旧/新 ID の見つけ方（どちらも VS Code のコマンドパレットから）:
     - 旧（NAS）側を開いた状態で「Developer: Open Workspace Storage」を実行 → 開いたフォルダが旧 ID
     - 新（ローカル）側を開いた状態で同コマンド → 開いたフォルダが新 ID
   - 旧 ID 配下のファイルをすべて新 ID へコピー（`state.vscdb` などを含めて上書き）

6. ローカルの `.code-workspace` を開く

   - この時点で Copilot のチャット履歴が復活しているはずです
   - ただし `.code-workspace` の中には、まだ NAS のフォルダ参照が残っている状態です

7. 「ファイル」→「フォルダーをワークスペースに追加…」でローカルの `timecard-system` を追加

   - エクスプローラーには同名フォルダが 2 つ（NAS とローカル）見える状態になります（多ルート構成）

8. エクスプローラーで NAS 側の `timecard-system` を右クリック → 「ワークスペースからフォルダーを削除」
   - 以後はローカルフォルダのみがワークスペースに残る
   - Copilot 会話は新 ID に載っているため継続利用可能

> 補足: 上記 2〜8 の流れは「ワークスペース定義（.code-workspace）を橋渡し器」にして、新 ID を自然に発生させつつ、旧 ID の状態を持ち込むトリックです。再現性が高く、失敗時のリカバリもしやすいです。

---

## 代替手順（直接 ID コピー法）

より短いものの、手順ミス時に復元が手間になることがあります。上級者向け。

1. 両 PC の VS Code を終了（Syncthing で `workspaceStorage` の同期は一時停止推奨）
2. ローカル新パスのフォルダを VS Code で一度開いて閉じ、`workspaceStorage` の新 ID を作る
3. `%APPDATA%\Code\User\workspaceStorage` で旧 ID → 新 ID へ内容をコピー
4. 必要であれば新 ID 配下の `workspace.json` の `folder` URI をローカルのエンコード済みパスに合わせて修正（例: `file:///c%3A/Users/ma/Desktop/timecard-system`）
5. VS Code を起動してローカルフォルダ（または `.code-workspace`）を開く

注意:

- 旧/新の識別を誤ると履歴が見えないことがあります。必ずコマンド「Developer: Open Workspace Storage」で ID を確認してください。
- 両 PC 同時に“新 ID”を開く前にコピーを済ませると安全です。

---

## Syncthing 運用の注意

- `workspaceStorage` を同期する場合は「同時に同じワークスペースを開かない」運用が安全です（競合の原因）。
- パスは両 PC で一致させる（例: どちらも `C:\Users\ma\Desktop\timecard-system`）。
- 競合リスクを避けたい場合は `workspaceStorage` を同期対象から除外し、必要時に手動で移行してください。

---

## トラブルシューティング

- Copilot の会話が復元しない
  - 新しいワークスペースを一度も開いていない → 新 ID が生成されていない可能性。開いて閉じる
  - 旧 ID と新 ID を取り違えた → 「Developer: Open Workspace Storage」で再確認
  - `.code-workspace` がまだ NAS のパスのみを指している → 多ルート化してローカルを追加 →NAS を削除
- ファイル監視エラー（ECONNRESET など）が続く
  - UNC/NAS を直接開いていないか確認。ローカルフォルダで開く
  - 監視負荷軽減（プロジェクト `.vscode/settings.json` の例）:
    ```json
    {
      "files.watcherExclude": {
        "**/.git/**": true,
        "**/node_modules/**": true
      },
      "search.exclude": {
        "**/.git": true,
        "**/node_modules": true
      }
    }
    ```
- うっかり新しいワークスペースを開く前にコピーしてしまった
  - もう一度新ワークスペースを開いて閉じてから、旧 ID → 新 ID へコピーし直す

---

## 参考（PowerShell コマンド）

- `workspaceStorage` をエクスプローラーで開く:
  ```powershell
  explorer "$env:APPDATA\Code\User\workspaceStorage"
  ```
- ローカルへコピー（属性保持／例）:
  ```powershell
  robocopy "\\MA-NAS\web\timecard\timecard-system" "$env:USERPROFILE\Desktop\timecard-system" /E /COPY:DAT /DCOPY:T /R:2 /W:2 /MT:16 /XJ
  ```

---

## まとめ

- 「多ルート・ワークスペースで橋渡し → 旧 ID の中身を新 ID へコピー → NAS ルートを外す」という流れが再現性高く、Copilot の会話も維持できます。
- 以後の編集は必ずローカルフォルダで行い、NAS はバックアップ／配信用に限定すると安定します。
