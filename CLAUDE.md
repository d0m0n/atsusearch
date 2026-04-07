# AtsuSearch - 熱中症対策 暑さ指数(WBGT)アプリ

## プロジェクト概要

**AtsuSearch（アツサーチ）** — 「暑さを検索、安全を発見」
熱中症対策のために暑さ指数(WBGT)をリアルタイムで検索・表示するWebアプリ。環境省の「暑さ指数(WBGT)予測値等 電子情報提供サービス」のCSVデータとGoogle Mapsを組み合わせ、住所入力またはGPS位置情報から熱中症リスクを可視化する。

## キャッチコピー
**「暑さを検索、安全を発見」**

## 技術スタック

- **Backend**: Laravel 12 (PHP 8.4)
- **Frontend**: Vue 3 + TypeScript + Vite
- **CSS**: Tailwind CSS 4（デジタル庁デザインシステム準拠のカスタムテーマ）
- **DB**: MySQL 8.4
- **Cache/Session**: Redis
- **Container**: Docker + Laravel Sail
- **Map**: Google Maps JavaScript API + Places API + Geocoding API
- **外部データ**: 環境省 WBGT CSV（https://www.wbgt.env.go.jp/）

## コマンド

```bash
# 環境
sail up -d          # 起動
sail down           # 停止

# 開発
sail npm run dev    # フロントエンド開発サーバー（HMR）
sail artisan serve  # ※Sailでは不要、localhost:80で自動起動

# DB
sail artisan migrate
sail artisan migrate:fresh --seed
sail mysql

# テスト
sail test                              # 全テスト
sail test --filter=WbgtServiceTest     # 単体
sail composer pint                     # コードフォーマット

# ビルド
sail npm run build

# WBGT データ更新
sail artisan atsusearch:update-wbgt
sail artisan atsusearch:update-wbgt --force
```

## ディレクトリ構成

```
atsusearch/
├── app/
│   ├── Http/Controllers/
│   │   ├── Api/
│   │   │   ├── LocationController.php    # 地点CRUD・お気に入り
│   │   │   ├── WbgtController.php        # WBGT取得・一括取得
│   │   │   ├── GeocodingController.php   # 逆ジオコーディング
│   │   │   └── AlertController.php       # アラート取得
│   │   └── Auth/                         # 認証（Laravel Breeze）
│   ├── Models/
│   │   ├── User.php
│   │   ├── Location.php
│   │   ├── WbgtData.php
│   │   └── HeatAlert.php
│   ├── Services/
│   │   ├── WbgtDataService.php           # 環境省CSV取得・パース・保存
│   │   ├── WbgtCsvParser.php             # CSVフォーマット解析（予測値/実況値）
│   │   ├── AlertService.php              # アラート取得・発行
│   │   └── NearestStationService.php     # 緯度経度→最寄り観測地点マッチング
│   ├── Console/Commands/
│   │   └── UpdateWbgtData.php            # artisanバッチコマンド
│   └── Events/
│       └── HeatAlertIssued.php
├── resources/
│   ├── js/
│   │   ├── app.ts
│   │   ├── pages/                        # Inertia.jsページ
│   │   │   ├── Home.vue                  # トップ（検索＋現在地WBGT）
│   │   │   ├── Search.vue                # 地図検索結果
│   │   │   ├── Dashboard.vue             # ログイン後マイページ
│   │   │   └── Auth/                     # 認証画面
│   │   ├── components/
│   │   │   ├── map/
│   │   │   │   ├── AtsuSearchMap.vue     # Google Maps本体
│   │   │   │   └── LocationMarker.vue    # マーカー
│   │   │   ├── wbgt/
│   │   │   │   ├── WbgtCard.vue          # WBGT表示カード
│   │   │   │   ├── WbgtLevelBadge.vue    # 危険度バッジ
│   │   │   │   └── WbgtTimeline.vue      # 時間帯別推移
│   │   │   ├── alert/
│   │   │   │   └── HeatAlertBanner.vue   # アラートバナー
│   │   │   └── ui/                       # デジタル庁DS準拠の共通UI
│   │   │       ├── DaButton.vue
│   │   │       ├── DaInput.vue
│   │   │       ├── DaCard.vue
│   │   │       ├── DaNotification.vue
│   │   │       └── DaBottomNav.vue
│   │   ├── composables/
│   │   │   ├── useGeolocation.ts         # GPS取得
│   │   │   ├── useWbgt.ts                # WBGTデータフェッチ
│   │   │   └── useGoogleMaps.ts          # Maps初期化
│   │   └── types/
│   │       └── index.ts                  # 型定義
│   └── css/
│       └── app.css                       # Tailwind + DADSカスタムテーマ
├── routes/
│   ├── web.php                           # Inertia.jsルート
│   └── api.php                           # REST API
├── database/
│   ├── migrations/
│   └── seeders/
│       └── WbgtStationSeeder.php         # 全841地点マスタ
├── config/
│   └── services.php                      # wbgt, google_maps 設定
└── CLAUDE.md
```

## アーキテクチャ方針

### 認証
- **未ログイン**: 住所入力・GPS→WBGT表示・対策表示を制限なく利用可能
- **ログイン時**（Laravel Breeze + Inertia.js）: 初期表示住所の保存、検索履歴、複数地域お気に入り一覧、アラート通知設定
- ユーザー登録は任意。認証なしでコア機能は完結する

### データフロー
1. ユーザーが住所入力 or GPS位置情報を送信
2. `NearestStationService` が緯度経度から最寄りのWBGT観測地点（全841地点）をマッチング
3. `WbgtDataService` がキャッシュ or DBから当該地点のWBGTデータを返却
4. フロントエンドが危険度レベルに応じた色分け・対策テキストを表示

### 環境省CSVデータ仕様

**運用期間**: 毎年4月第4水曜日〜10月22日頃

**予測値CSV**（`yohou_all.csv` / `yohou_{地点番号}.csv`）:
- 1行目: 空,空,予測対象時刻(YYYYMMDDHH)...
- 2行目〜: 地点番号,作成時刻,予測値(×10)...
- 予測値は10で割って使用（例: 310 → 31.0℃）
- 当日〜翌々日24時まで

**実況値CSV**（`wbgt_{地点番号}_{YYYYMM}.csv`）:
- 1行目: Date,Time,地点番号...
- 2行目〜: 日付,時刻,WBGT値...
- 月単位ファイル

**更新頻度**: 1日3回（5時, 14時, 17時）  
**全国地点数**: 841地点  
**文字コード**: ASCII（半角英数記号のみ）  
**区切り**: カンマ、改行はLF

### WBGT危険度レベル

| WBGT | レベル | 色 | 対策 |
|------|--------|------|------|
| 31以上 | 危険 | 赤 `#D32F2F` | 運動は原則中止。外出をなるべく避ける |
| 28〜31 | 厳重警戒 | 橙 `#F57C00` | 激しい運動は中止。10〜20分おきに休憩 |
| 25〜28 | 警戒 | 黄 `#FBC02D` | 積極的に休憩。水分・塩分補給 |
| 21〜25 | 注意 | 水色 `#0288D1` | 水分補給を忘れずに |
| 21未満 | ほぼ安全 | 緑 `#388E3C` | 適宜水分補給 |

## UI設計 — デジタル庁デザインシステム(DADS)準拠

参照: https://design.digital.go.jp/dads/

### 基本原則
- **シンプル・視認性重視**: 装飾を最小限に、情報の優先度を明確にする
- **アクセシビリティ**: WCAG 2.1 AA準拠。色だけに頼らずテキスト・アイコン併用
- **レスポンシブ**: モバイルファースト。320px〜対応
- **アニメーション**: 画面遷移は `ease-out 200ms`、ボタンホバーは `ease 150ms`。派手にせず「心地よさ」重視。`prefers-reduced-motion` 対応必須

### DADSデザイントークン（Tailwindカスタムテーマに反映）

**カラー**:
- テキスト: `#1A1A1C`（本文）, `#626264`（補助）
- 背景: `#FFFFFF`（メイン）, `#F1F1F4`（セクション背景）
- プライマリ: `#0017C1`（リンク・主要ボタン）
- ボーダー: `#D9D9DB`
- エラー: `#EC0000`
- 成功: `#259D63`

**タイポグラフィ**:
- フォント: `"Noto Sans JP", "Hiragino Sans", sans-serif`
- 本文: 16px / line-height 1.7
- 見出し: 太字、適切なサイズ階層

**角丸**: 小要素 `4px`、カード `8px`、モーダル `12px`

**余白**: 8px単位のグリッド（8, 16, 24, 32, 40, 48...）

### 画面構成

**トップページ（Home.vue）**:
- ヘッダー: アプリ名 + ハンバーガーメニュー
- 検索エリア: 住所入力フィールド + 「現在地で検索」ボタン
- 結果カード: WBGTレベル表示（色分け＋テキスト＋アイコン）
- 熱中症対策テキスト: レベルに応じた具体的アドバイス
- アラートバナー: 熱中症警戒アラート発表時に上部に固定表示

**検索結果（Search.vue）**:
- Google Maps表示（上半分 or 左カラム）
- WBGT情報カード（下半分 or 右カラム）
- 時間帯別WBGTタイムライン

**ダッシュボード（Dashboard.vue）**:※要ログイン
- 登録地域一覧（カード形式、各地点のWBGT表示）
- 検索履歴
- 初期表示地域の設定

### アニメーション指針
- **ページ遷移**: Inertia.js のページ切り替え時に `opacity 0→1` + `translateY(8px→0)` 200ms ease-out
- **カード表示**: stagger付き fade-in（各カード50msずらし）
- **WBGT値更新**: 数値の切り替えに `transition` で滑らかな色変化
- **ボタン**: `transform: scale(0.98)` on press, `scale(1)` on release, 150ms
- **アラートバナー**: 上からスライドイン 300ms ease-out
- `prefers-reduced-motion: reduce` の場合は全アニメーションを無効化

## API設計

### 公開API（認証不要）

```
GET  /api/wbgt?lat={lat}&lng={lng}           # 緯度経度からWBGT取得
GET  /api/wbgt?address={address}              # 住所からWBGT取得
GET  /api/wbgt/{station_id}                   # 観測地点IDで直接取得
GET  /api/wbgt/{station_id}/timeline          # 時間帯別データ
GET  /api/alerts?prefecture={code}            # アラート取得
GET  /api/stations/nearest?lat={lat}&lng={lng} # 最寄り観測地点
```

### 認証付きAPI（Laravel Sanctum）

```
GET    /api/user/locations                    # お気に入り地点一覧
POST   /api/user/locations                    # お気に入り登録
DELETE /api/user/locations/{id}               # お気に入り削除
GET    /api/user/history                      # 検索履歴
PUT    /api/user/settings                     # 初期表示地域等の設定
```

## DB設計

### テーブル

- `users` — Laravel Breeze標準 + `default_latitude`, `default_longitude`, `default_address`
- `wbgt_stations` — 全841観測地点マスタ（station_code, name, prefecture_code, latitude, longitude）
- `wbgt_data` — WBGTデータ（station_id, datetime, wbgt_value, data_type[forecast/actual]）
- `heat_alerts` — アラート（prefecture_code, alert_type, target_date, issued_at, is_active）
- `user_locations` — ユーザーお気に入り地点（user_id, station_id, label, sort_order）
- `search_histories` — 検索履歴（user_id, query, latitude, longitude, station_id, searched_at）

## 環境変数（.env）

```
APP_NAME=AtsuSearch
APP_TIMEZONE=Asia/Tokyo

DB_CONNECTION=mysql
DB_HOST=mysql
DB_DATABASE=atsusearch
DB_USERNAME=sail
DB_PASSWORD=password

CACHE_STORE=redis
SESSION_DRIVER=database
REDIS_HOST=redis

GOOGLE_MAPS_API_KEY=your_key_here

# 環境省WBGT
WBGT_BASE_URL=https://www.wbgt.env.go.jp/prev15WG/dl/
WBGT_ALERT_URL=https://www.wbgt.env.go.jp/alert_data/
WBGT_CACHE_TTL=3600
```

## コーディング規約

- PHP: PSR-12。Laravel Pint で自動整形
- TypeScript: strict mode。`any` 禁止
- Vue: `<script setup lang="ts">` + Composition API のみ
- CSS: Tailwind ユーティリティ優先。カスタムCSSは `@apply` で最小限
- コミットメッセージ: `feat:`, `fix:`, `refactor:`, `docs:`, `test:` プレフィックス
- テスト: Feature テストでAPIエンドポイントを網羅。Service 層はUnit テスト

## 開発時の注意

- `npm` コマンドは必ず `sail npm` で実行する（ホスト環境との不整合を防ぐ）
- 環境省CSVの運用期間外（10月下旬〜翌4月下旬）はデータが空になる。シーダーでダミーデータを用意する
- Google Maps API は SKU ごとの無償利用回数制限あり（2025年3月〜）。開発時のリクエスト数に注意
- WBGT予測値は「10で割る」変換が必要。パーサーで必ず実施
- `prefers-reduced-motion` 対応を全アニメーションに適用する
