# プロジェクト合意サマリ（引き継ぎ用）

- 365 日営業。サイドメニューはフォーカストラップ＋初期フォーカス済み。
- バッジは common.css（info/warn/success/danger/critical）。ハンバーガー背景・hover あり。
- 管理者 API は catch 時に error_log へ記録。

- 個別既読あり。未読バッジ即時更新。

- 打刻アラート
  - 種別トグル: 未退勤=ON, 未打刻=ON, 勤務中=OFF（localStorage 保持）。
  - 期間セレクタ: 3/5/7 日。上書き（全休/午前/午後/無視）は除外してから集計・表示。
  - 期間セレクタ: 3/5/7 日。除外は `day_status_effective` 参照で `status IN ('off','ignore')` に統一（半休は除外しない）。
  - 非ちらつき更新: カード単独再描画。深掘りは admin_attendance へディープリンク。
- 今日の勤務（一覧）

  - 全ユーザー表示（visible=1）。行ボタンで上書き: off_full/off_am/off_pm/ignore/取消。ボタンの active で状態を表現。
  - メモ送信可。手動更新＋ 60 秒ポーリング。
  - 実績バッジ表示: 未打刻/勤務中/休憩中/退勤済み（api/today_status_summary.php）。

- ディープリンクは periodStart/End を考慮して属する月度へ自動切替。
- 対象日はフィルタに関わらず強制可視化＋自動展開、安定後にハイライト解除。
- `back`/`backMode=auto|manual` に対応。保存/キャンセル/閉じる/削除/復元後に自動戻り（auto）。

- 追記型: set は既存有効を revoke→INSERT、clear は revoke のみ。監査性を優先。
- 参照は `day_status_effective` に統一。UI は本人用 `day_status_self_set.php` / `day_status_self_clear.php` を使用。

- attendance_alerts.php は LIMIT 整数直埋め。未打刻は過去日ベース（当日は含めない）で、上書き該当日は除外。
- attendance_alerts.php は LIMIT 整数直埋め。未打刻は過去日ベース（当日は含めない）。
- 除外は `day_status_effective` 参照で `status IN ('off','ignore')` を一貫除外（半休は除外しない）。
- バッジ: 未退勤=critical、勤務中=info、未打刻=warn。

- アラート行は種別に応じた左ボーダー＋淡背景（type-info/warn/critical）。
- 月次一覧に日バッジ（work/off/am_off/pm_off/ignore, source=O）を表示。

- 失効カード: 失効日に失効する時間数の表示。
- 勤怠表に有給取得行も表示（勤務行とマージ）。
- 未打刻の真偽判定のサーバ側実装（休日/公休/休暇/上書き除外の厳密化）。
- 未打刻の真偽判定のサーバ側実装（休日/公休/休暇の厳密化）は effective 参照への移行で方針確定。残は性能/UX 調整。
- 勤怠記録に「休みの明示」統合（表示/集計の更なる反映）。
- 予定表/シフト導入（将来、未打刻判定の精度向上に活用）。
- 決裁結果の申請者メール通知は社内ポリシー決定まで保留。

## 勤務時間算定の一元化（2025-12-03）

- 共通ライブラリ `api/_lib_worktime.php` を導入し、勤務時間算定式を統一
- ビュー必須: `day_status_effective`（`off_full→off` に正規化前提）
- 単位整合: API は分（minutes）で返却、丸めは UI 側適用
- 置換済み: `flex_summary.php`, `attendance_avg.php`, `attendance_alerts.php`, `my_alerts.php`
- 保留: `dashboard_summary.php` は勤務時間集計なし（必要なら拡張で対応）
- 決裁結果の申請者メール通知は社内ポリシー決定まで保留。
