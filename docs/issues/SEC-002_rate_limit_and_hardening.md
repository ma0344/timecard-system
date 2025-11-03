# [SEC-002] レートリミットと妥当性強化（承認リンク/決裁）

Meta

- Milestone: Approval hardening
- Project: v1.1 Approval & Security
- Priority: P0
- Labels: area/backend, security, priority/P0
- Dependencies: SEC-001（順不同可だが併せて進めると良い）
- Estimate: 0.5〜1 日
- Assignees: （未割当）

背景

- 承認リンクの総当りや多重叩きを抑制したい。
- 正常利用者に影響を与えない範囲で、簡易レート制御と失敗時遅延を入れる。

やること

1. レート制御
   - IP ベースの短期カウント（メモリ or 軽量テーブル）
   - 閾値超過時 429 または指数的遅延
2. 妥当性強化
   - decide は POST のみ受付
   - 可能なら簡易な Origin/Referer チェック

受け入れ基準

- 短時間で多数試行すると 429 または遅延
- 正常操作では体感差なし

チェックリスト

- [ ] レート制御の実装
- [ ] 閾値/期間の設定値化
- [ ] decide のメソッド/ヘッダ検査
- [ ] 負荷/誤判定の簡易テスト
