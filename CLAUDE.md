# AtsuSearch - 熱中症対策 暑さ指数(WBGT)アプリ

## プロジェクト概要

**AtsuSearch（アツサーチ）**は、熱中症対策のために複数の地域の暑さ指数(WBGT)をリアルタイムで検索・表示できるWebアプリケーションです。環境省の熱中症予防情報サイトのデータを活用し、Googleマップから直感的に場所を指定して暑さ指数を確認できます。

### キャッチコピー
**「暑さを検索、安全を発見」**

### AtsuSearchの特徴
- 🗺️ **マップベース検索**: Googleマップで直感的に場所を選択
- 📊 **リアルタイムWBGT**: 環境省データによる正確な暑さ指数表示
- 🚨 **アラート機能**: 危険レベルに応じた視覚的な警告
- 📱 **レスポンシブ対応**: スマートフォンからデスクトップまで対応
- 🏥 **健康重視**: 科学的根拠に基づく熱中症対策支援

### Laravel 12について

Laravel 12は2025年2月24日にリリースされた最新版で、**ゼロブレイキングチェンジ**を実現した画期的なバージョンです。既存のLaravel 11アプリケーションからほぼ無修正でアップグレードが可能で、新しいスターターキット（Vue + TypeScript）やTailwind CSS 4統合などの現代的な開発環境を提供します。

## 技術スタック

- **フレームワーク**: Laravel 12（2025年2月24日リリース）
- **PHP**: PHP 8.4（Laravel 12対応）
- **コンテナ**: Docker & Docker Compose（Laravel Sail）
- **データベース**: MySQL 8.4（Laravel Sailデフォルト）
- **フロントエンド**: Vue.js 3 + TypeScript + Vite
- **スタイリング**: Tailwind CSS 4
- **マップAPI**: Google Maps JavaScript API

## システム要件

### 機能要件

1. **地域選択機能**
   - Googleマップを使用した直感的な場所指定
   - 複数地域の同時選択・管理
   - 地域のお気に入り登録機能

2. **WBGT表示機能**
   - リアルタイムの暑さ指数表示
   - 予測値と実況値の両方に対応
   - 危険度レベルによる色分け表示

3. **アラート機能**
   - 熱中症警戒アラート・特別警戒アラートの表示
   - 設定した危険度を超えた場合の通知

4. **データ管理機能**
   - 過去のWBGTデータの保存・表示
   - CSVエクスポート機能

### 非機能要件

- レスポンシブデザイン（スマートフォン対応）
- API呼び出し頻度の最適化
- データキャッシュによる高速表示

## データソース

### 環境省 熱中症予防情報サイト

環境省では「暑さ指数（WBGT）予測値等電子情報提供サービス」としてCSV形式でWBGTデータを提供しています。

**提供データ**:
- 全国の暑さ指数（WBGT）の予測値、実況値
- 熱中症警戒アラート・熱中症特別警戒アラートの発表情報
- 情報提供期間：2025年4月23日（水）～2025年10月22日（水）

**API仕様**:
- データ形式: CSV
- 更新頻度: 1日3回（5時、14時、17時）
- 全国約840地点の暑さ指数情報

### Google Maps Platform

**必要なAPI**:
- Maps JavaScript API（地図表示・場所選択）
- Places API（住所検索・オートコンプリート）
- Geocoding API（住所→座標変換）

**注意事項**:
- 2025年3月1日より無償枠月200USDが廃止され、SKU（機能）ごとに無償利用回数が適用
- APIキーの取得と制限設定が必要

## 環境構築

### 必要なソフトウェア

- Docker Desktop
- Git
- Composer（グローバルインストール）

### Laravel 12 + Sail システム要件

- **PHP**: 8.4（Sailで自動構成）
- **データベース**: MySQL 8.4（Sailで自動構成）
- **Node.js**: 18.x（Sailで自動構成）
- **必須PHP拡張**: Laravel Sailで自動インストール

### ディレクトリ構成

```
atsusearch/
├── app/                    # Laravelアプリケーション
├── bootstrap/
├── config/
├── database/
├── resources/
├── routes/
├── storage/
├── tests/
├── vendor/
├── docker-compose.yml      # Laravel Sailで自動生成
├── .env.example
├── .env
├── artisan
├── composer.json
├── package.json
├── CLAUDE.md
└── README.md
```

### Laravel Sail環境設定

Laravel Sailを使用することで、複雑なDocker設定を自動化し、開発に集中できます。

#### Sailの特徴
- **Laravel 12最適化**: PHP 8.4、PostgreSQL 16対応
- **自動セットアップ**: Docker環境の自動構築
- **開発効率化**: Xdebug、Redis、Mailhog統合
- **簡単コマンド**: `sail up`で環境起動

#### 利用可能サービス
- **Webサーバー**: Nginx (ポート80)
- **データベース**: MySQL 8.4 (ポート3306)
- **キャッシュ**: Redis (ポート6379)
- **メールテスト**: Mailhog (ポート8025)
- **開発支援**: Xdebug対応

### 環境変数設定

#### .env設定（Sail用）

```bash
# アプリケーション設定
APP_NAME="AtsuSearch"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_TIMEZONE=Asia/Tokyo
APP_URL=http://localhost

# データベース設定（Sail自動構成）
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=atsusearch
DB_USERNAME=sail
DB_PASSWORD=password

# Sail設定
SAIL_XDEBUG_MODE=develop,debug,coverage

# Google Maps API
GOOGLE_MAPS_API_KEY=your_google_maps_api_key_here

# 環境省API設定
WBGT_API_BASE_URL=https://www.wbgt.env.go.jp/
WBGT_API_CACHE_DURATION=3600

# セッション・キャッシュ設定
SESSION_DRIVER=database
CACHE_STORE=redis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

# メール設定（開発用）
MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@atsusearch.com"
MAIL_FROM_NAME="${APP_NAME}"
```

## セットアップ手順

### 1. AtsuSearchプロジェクトの作成

```bash
# Laravel 12プロジェクトをSailで作成（MySQLはデフォルト）
curl -s "https://laravel.build/atsusearch?with=mysql,redis,mailhog" | bash

# プロジェクトディレクトリに移動
cd atsusearch
```

### 2. Sail環境の起動

```bash
# 初回はSailエイリアスを設定
alias sail='[ -f sail ] && sh sail || sh vendor/bin/sail'

# Sailコンテナの起動（初回は時間がかかります）
sail up -d

# 起動確認
sail ps
```

### 3. Laravel環境のセットアップ

```bash
# アプリケーションキーの生成
sail artisan key:generate

# データベースマイグレーション
sail artisan migrate

# ストレージリンクの作成
sail artisan storage:link

# NPMパッケージのインストール
sail npm install
```

### 4. フロントエンド環境の構築

```bash
# Vue.js 3 + TypeScript スターターキット（Laravel 12新機能）
sail artisan install:broadcasting

# 追加パッケージのインストール
sail npm install @google/maps axios

# 開発サーバーの起動（ホットリロード対応）
sail npm run dev
```

### 5. 確認とテスト

```bash
# ブラウザでアクセス
# http://localhost （AtsuSearchアプリ）
# http://localhost:8025 （Mailhog - メールテスト）

# テストの実行
sail test

# コードフォーマット確認
sail composer pint
```

## 開発ワークフロー

### 日常的なSailコマンド

#### 環境管理
```bash
# 環境起動
sail up -d

# 環境停止
sail down

# 環境再構築
sail build --no-cache

# ログ確認
sail logs
```

#### Laravel開発
```bash
# Artisanコマンド実行
sail artisan migrate
sail artisan make:controller LocationController
sail artisan route:list

# Composerコマンド
sail composer install
sail composer require package-name

# テスト実行
sail test
sail test --filter=LocationTest
```

#### フロントエンド開発
```bash
# 開発サーバー（ホットリロード）
sail npm run dev

# 本番ビルド
sail npm run build

# ウォッチモード
sail npm run dev -- --watch
```

#### データベース操作
```bash
# マイグレーション
sail artisan migrate
sail artisan migrate:fresh --seed

# データベース接続
sail mysql

# バックアップ作成
sail exec mysql mysqldump -u sail -p atsusearch > backup.sql
```

### デバッグとログ
```bash
# Xdebugの有効化
SAIL_XDEBUG_MODE=develop,debug,coverage sail up -d

# ログテイル
sail logs -f laravel.test

# Mailhogでメール確認
# http://localhost:8025
```

### テーブル構成

#### locations（地点管理）

```sql
CREATE TABLE locations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL COMMENT '地点名',
    address TEXT COMMENT '住所',
    latitude DECIMAL(10, 8) NOT NULL COMMENT '緯度',
    longitude DECIMAL(11, 8) NOT NULL COMMENT '経度',
    prefecture_code VARCHAR(2) COMMENT '都道府県コード',
    is_favorite BOOLEAN DEFAULT FALSE COMMENT 'お気に入り',
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    
    INDEX idx_coordinates (latitude, longitude),
    INDEX idx_prefecture (prefecture_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### wbgt_data（暑さ指数データ）

```sql
CREATE TABLE wbgt_data (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    location_id BIGINT UNSIGNED NOT NULL,
    date DATE NOT NULL COMMENT '対象日',
    hour TINYINT UNSIGNED NOT NULL COMMENT '時間（0-23）',
    wbgt_value DECIMAL(4, 1) COMMENT '暑さ指数',
    data_type ENUM('actual', 'forecast') NOT NULL COMMENT 'データ種別',
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    
    FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE CASCADE,
    UNIQUE KEY unique_wbgt (location_id, date, hour, data_type),
    INDEX idx_date_hour (date, hour),
    
    CHECK (hour <= 23)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### heat_alerts（熱中症アラート）

```sql
CREATE TABLE heat_alerts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    prefecture_code VARCHAR(2) NOT NULL COMMENT '都道府県コード',
    alert_type ENUM('normal', 'warning', 'special_warning') NOT NULL COMMENT 'アラート種別',
    target_date DATE NOT NULL COMMENT '対象日',
    issued_at TIMESTAMP NOT NULL COMMENT '発表日時',
    is_active BOOLEAN DEFAULT TRUE COMMENT '有効フラグ',
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    
    INDEX idx_prefecture_date (prefecture_code, target_date),
    INDEX idx_issued_at (issued_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## API設計とテスト

### エンドポイント一覧

#### 地点管理API

```php
// routes/api.php

// 地点の登録
POST /api/locations
{
    "name": "東京駅",
    "address": "東京都千代田区丸の内1丁目",
    "latitude": 35.6812,
    "longitude": 139.7671
}

// 地点一覧取得
GET /api/locations

// お気に入り設定
PUT /api/locations/{id}/favorite

// 地点削除
DELETE /api/locations/{id}
```

#### WBGT データAPI

```php
// 指定地点のWBGTデータ取得
GET /api/wbgt/{location_id}?date=2025-07-21&type=forecast

// 複数地点のWBGTデータ一括取得
POST /api/wbgt/bulk
{
    "location_ids": [1, 2, 3],
    "date": "2025-07-21",
    "type": "forecast"
}
```

### Sailでのテスト実行

```bash
# 全テスト実行
sail test

# 特定テスト実行
sail test --filter=LocationApiTest

# カバレッジ付きテスト
sail test --coverage

# フィーチャーテスト
sail artisan test tests/Feature/LocationTest.php
```

## データベース設計

## 主要機能の実装

### 1. WBGTデータ取得サービス（Sail環境対応）

```php
<?php
// app/Services/WbgtDataService.php

namespace App\Services;

use App\Models\Location;
use App\Models\WbgtData;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class WbgtDataService
{
    private const CACHE_TTL = 3600; // 1時間

    public function fetchAndStoreWbgtData(): void
    {
        $cacheKey = 'wbgt_last_update';
        $lastUpdate = Cache::get($cacheKey);
        
        if ($lastUpdate && now()->diffInMinutes($lastUpdate) < 60) {
            Log::info('WBGT data update skipped - recent update found');
            return;
        }

        try {
            $csvData = $this->fetchCsvFromEnvironmentAgency();
            $this->processCsvData($csvData);
            
            Cache::put($cacheKey, now(), self::CACHE_TTL);
            Log::info('WBGT data updated successfully');
        } catch (\Exception $e) {
            Log::error('WBGT data update failed: ' . $e->getMessage());
            throw $e;
        }
    }

    private function fetchCsvFromEnvironmentAgency(): string
    {
        $response = Http::timeout(30)->get(config('services.wbgt.api_url'));
        
        if (!$response->successful()) {
            throw new \Exception('WBGTデータの取得に失敗しました');
        }
        
        return $response->body();
    }

    private function processCsvData(string $csvData): void
    {
        $lines = explode("\n", $csvData);
        $header = str_getcsv(array_shift($lines));
        
        foreach ($lines as $line) {
            if (empty(trim($line))) continue;
            
            $data = str_getcsv($line);
            $this->storeWbgtRecord($header, $data);
        }
    }

    private function storeWbgtRecord(array $header, array $data): void
    {
        $record = array_combine($header, $data);
        
        WbgtData::updateOrCreate([
            'location_id' => $this->getLocationIdByCode($record['location_code']),
            'date' => $record['date'],
            'hour' => $record['hour'],
            'data_type' => $record['type']
        ], [
            'wbgt_value' => $record['wbgt'] ?? null
        ]);
    }
}
```

### Sailでのサービステスト

```bash
# WBGTサービスのテスト作成
sail artisan make:test WbgtDataServiceTest --unit

# テスト実行
sail test --filter=WbgtDataServiceTest

# 実際のAPI呼び出しテスト（開発環境）
sail artisan wbgt:update
```

### 2. Google Maps統合（Vite + Sail対応）

```javascript
// resources/js/components/AtsuSearchMap.vue

<template>
  <div>
    <div id="map" class="w-full h-96 rounded-lg shadow-lg"></div>
    <div class="mt-4">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div
          v-for="location in selectedLocations"
          :key="location.id"
          class="bg-white p-4 rounded-lg shadow"
        >
          <h3 class="font-bold text-lg">{{ location.name }}</h3>
          <div class="mt-2">
            <span
              :class="getWbgtLevelClass(location.current_wbgt)"
              class="px-2 py-1 rounded text-white font-bold"
            >
              WBGT: {{ location.current_wbgt }}
            </span>
          </div>
          <p class="text-sm text-gray-600 mt-1">
            {{ getWbgtLevelText(location.current_wbgt) }}
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import axios from 'axios'

// TypeScript型定義
interface Location {
  id: number
  name: string
  current_wbgt: number
  latitude: number
  longitude: number
}

const map = ref<google.maps.Map | null>(null)
const selectedLocations = ref<Location[]>([])

const initMap = () => {
  map.value = new google.maps.Map(document.getElementById('map')!, {
    center: { lat: 35.6812, lng: 139.7671 }, // 東京駅
    zoom: 10,
    styles: [
      {
        featureType: 'poi',
        elementType: 'labels',
        stylers: [{ visibility: 'off' }]
      }
    ]
  })

  // 地図クリックイベント
  map.value.addListener('click', (event: google.maps.MapMouseEvent) => {
    if (event.latLng) {
      addLocationMarker(event.latLng)
    }
  })
}

const addLocationMarker = async (latLng: google.maps.LatLng) => {
  try {
    const response = await axios.post('/api/geocode/reverse', {
      latitude: latLng.lat(),
      longitude: latLng.lng()
    })

    const locationData = response.data
    
    // マーカーを地図に追加
    const marker = new google.maps.Marker({
      position: latLng,
      map: map.value,
      title: locationData.name
    })

    // WBGTデータを取得して表示
    await fetchWbgtData(locationData.id)
    
  } catch (error) {
    console.error('地点追加エラー:', error)
  }
}

const fetchWbgtData = async (locationId: number) => {
  try {
    const response = await axios.get(`/api/wbgt/${locationId}`)
    const data = response.data
    
    // 選択された地点リストを更新
    const existingIndex = selectedLocations.value.findIndex(
      loc => loc.id === locationId
    )
    
    if (existingIndex >= 0) {
      selectedLocations.value[existingIndex] = data.location
    } else {
      selectedLocations.value.push(data.location)
    }
    
  } catch (error) {
    console.error('WBGTデータ取得エラー:', error)
  }
}

const getWbgtLevelClass = (wbgt: number): string => {
  if (wbgt >= 31) return 'bg-red-600'      // 危険
  if (wbgt >= 28) return 'bg-orange-500'   // 厳重警戒
  if (wbgt >= 25) return 'bg-yellow-500'   // 警戒
  if (wbgt >= 21) return 'bg-blue-500'     // 注意
  return 'bg-green-500'                    // ほぼ安全
}

const getWbgtLevelText = (wbgt: number): string => {
  if (wbgt >= 31) return '危険：運動は原則中止'
  if (wbgt >= 28) return '厳重警戒：激しい運動は中止'
  if (wbgt >= 25) return '警戒：積極的に休憩'
  if (wbgt >= 21) return '注意：水分補給を忘れずに'
  return 'ほぼ安全'
}

onMounted(() => {
  // Google Maps APIの読み込み完了を待機
  if (window.google && window.google.maps) {
    initMap()
  } else {
    window.initMap = initMap
  }
})
</script>
```

### Sailでのフロントエンド開発

```bash
# 開発サーバー起動（ホットリロード対応）
sail npm run dev

# TypeScriptの型チェック
sail npm run type-check

# Vue.js開発ツールでデバッグ
# ブラウザ拡張機能と組み合わせて使用
```

### 3. リアルタイムアラート機能

```php
<?php
// app/Services/AlertService.php

namespace App\Services;

use App\Models\HeatAlert;
use App\Events\HeatAlertIssued;
use Illuminate\Support\Facades\Http;

class AlertService
{
    public function checkAndIssueAlerts(): void
    {
        $alertData = $this->fetchAlertDataFromApi();
        
        foreach ($alertData as $alert) {
            $existingAlert = HeatAlert::where([
                'prefecture_code' => $alert['prefecture_code'],
                'target_date' => $alert['target_date'],
                'alert_type' => $alert['alert_type']
            ])->first();
            
            if (!$existingAlert) {
                $newAlert = HeatAlert::create($alert);
                event(new HeatAlertIssued($newAlert));
            }
        }
    }
    
    private function fetchAlertDataFromApi(): array
    {
        // 環境省のアラートAPIからデータを取得
        $response = Http::get(config('services.wbgt.alert_api_url'));
        
        if (!$response->successful()) {
            throw new \Exception('アラートデータの取得に失敗しました');
        }
        
        return $response->json();
    }
}
```

## デプロイメント

### 本番環境への対応

⚠️ **注意**: Laravel Sailは開発環境専用です。本番環境では別のDocker構成を使用します。

#### 本番用Docker構成

```dockerfile
# Dockerfile.prod
FROM php:8.4-fpm-alpine

# システムパッケージのインストール
RUN apk add --no-cache \
    postgresql-dev \
    libzip-dev \
    zip \
    unzip \
    git

# PHP拡張のインストール
RUN docker-php-ext-install \
    pdo_pgsql \
    zip \
    opcache

# Composerのインストール
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# アプリケーションファイルのコピー
WORKDIR /var/www/html
COPY . .

# 依存関係のインストール（本番用）
RUN composer install --optimize-autoloader --no-dev

# アセットのビルド
RUN npm ci && npm run build

# 権限設定
RUN chown -R www-data:www-data storage bootstrap/cache

# 本番用最適化
RUN php artisan config:cache && \
    php artisan route:cache && \
    php artisan view:cache

EXPOSE 9000
CMD ["php-fpm"]
```

#### 本番用docker-compose.yml

```yaml
# docker-compose.prod.yml
version: '3.9'

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile.prod
    environment:
      - APP_ENV=production
      - APP_DEBUG=false
    volumes:
      - storage:/var/www/html/storage
    restart: unless-stopped

  web:
    image: nginx:alpine
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./nginx.prod.conf:/etc/nginx/conf.d/default.conf
      - ./ssl:/etc/nginx/ssl:ro
    depends_on:
      - app
    restart: unless-stopped

  db:
    image: mysql:8.4-alpine
    environment:
      MYSQL_DATABASE: ${DB_DATABASE}
      MYSQL_USER: ${DB_USERNAME}
      MYSQL_PASSWORD: ${DB_PASSWORD}
      MYSQL_ROOT_PASSWORD: ${DB_ROOT_PASSWORD}
    volumes:
      - mysql_data:/var/lib/mysql
    restart: unless-stopped

volumes:
  mysql_data:
  storage:
```

#### 開発から本番への移行手順

```bash
# 1. 開発環境での最終チェック
sail test
sail composer pint
sail npm run build

# 2. 本番用環境変数設定
cp .env.production .env

# 3. 本番用ビルド
docker-compose -f docker-compose.prod.yml build

# 4. 本番環境デプロイ
docker-compose -f docker-compose.prod.yml up -d

# 5. 本番環境初期化
docker-compose exec app php artisan migrate --force
docker-compose exec app php artisan storage:link
```

## パフォーマンス最適化

### Sailでの開発効率化

#### Redis統合によるキャッシュ戦略
```bash
# Redisを使ったキャッシュ設定（Sailで自動設定済み）
sail redis-cli

# キャッシュクリア
sail artisan cache:clear
sail artisan config:cache
sail artisan route:cache
```

#### データベース最適化
```bash
# MySQLパフォーマンス確認
sail mysql
# DESCRIBE wbgt_data; でテーブル情報確認
# SHOW INDEX FROM wbgt_data; でインデックス確認

# インデックス追加
sail artisan make:migration add_performance_indexes_to_wbgt_data

# クエリ最適化確認
sail artisan telescope:clear
```

#### Viteによるアセット最適化
```bash
# 開発時の高速ビルド
sail npm run dev

# 本番用最適化ビルド
sail npm run build

# バンドルサイズ分析
sail npm run build -- --analyze
```

### バッチ処理（Sail + Schedule）

Laravel 12では従来のスケジューラーに加え、新しいパイプライン機能で効率的なデータ処理が可能です。

```php
<?php
// app/Console/Commands/UpdateWbgtData.php

namespace App\Console\Commands;

use App\Services\WbgtDataService;
use Illuminate\Console\Command;

class UpdateWbgtData extends Command
{
    protected $signature = 'atsusearch:update-wbgt {--force : Force update even if recently updated}';
    protected $description = 'Update WBGT data from Environment Agency API';

    public function handle(WbgtDataService $service): int
    {
        try {
            $this->info('Starting WBGT data update...');
            
            if ($this->option('force')) {
                Cache::forget('wbgt_last_update');
            }
            
            $service->fetchAndStoreWbgtData();
            $this->info('✅ WBGT data updated successfully');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ WBGT data update failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
```

#### Sailでのスケジュール実行
```bash
# スケジュール確認
sail artisan schedule:list

# 手動実行
sail artisan atsusearch:update-wbgt

# 強制更新
sail artisan atsusearch:update-wbgt --force

# スケジューラー実行（本番環境）
sail artisan schedule:work
```

## セキュリティ対策

### API制限

1. **レート制限**: Laravel Sanctumでリクエスト数制限
2. **CORS設定**: 適切なオリジン設定
3. **SQL インジェクション対策**: Eloquent ORM使用
4. **XSS対策**: Bladeテンプレートのエスケープ

### データ保護

```php
<?php
// config/cors.php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [
        'http://localhost:8080',
        'https://your-domain.com'
    ],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
```

## 運用・保守

### Sailでの開発・運用

#### ログ監視とデバッグ
```bash
# リアルタイムログ監視
sail logs -f

# 特定サービスのログ
sail logs -f laravel.test
sail logs -f mysql
sail logs -f redis

# Laravelログ確認
sail artisan log:clear
tail -f storage/logs/laravel.log
```

#### データベース管理
```bash
# データベース接続
sail mysql

# バックアップ作成
sail exec mysql mysqldump -u sail -p atsusearch > backup_$(date +%Y%m%d).sql

# バックアップ復元
sail exec -T mysql mysql -u sail -p atsusearch < backup_20250721.sql

# マイグレーション管理
sail artisan migrate:status
sail artisan migrate:rollback --step=1
```

#### パフォーマンス監視
```bash
# Horizon（キュー監視）ダッシュボード
# http://localhost/horizon

# Telescope（デバッグ）ダッシュボード  
# http://localhost/telescope

# メール送信テスト（Mailhog）
# http://localhost:8025
```

### ヘルスチェック

```php
<?php
// routes/web.php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

Route::get('/health', function () {
    $status = [
        'status' => 'ok',
        'timestamp' => now()->toISOString(),
        'services' => []
    ];
    
    // データベース接続確認
    try {
        DB::connection()->getPdo();
        $status['services']['database'] = 'ok';
    } catch (\Exception $e) {
        $status['services']['database'] = 'error';
        $status['status'] = 'error';
    }
    
    // Redis接続確認
    try {
        Cache::store('redis')->put('health_check', 'ok', 60);
        $status['services']['redis'] = 'ok';
    } catch (\Exception $e) {
        $status['services']['redis'] = 'error';
    }
    
    // 環境省API接続確認（簡易）
    $lastUpdate = Cache::get('wbgt_last_update');
    $status['services']['wbgt_api'] = $lastUpdate && $lastUpdate->diffInHours(now()) < 2 ? 'ok' : 'warning';
    
    return response()->json($status);
});
```

#### 本番環境監視
```bash
# ヘルスチェック確認
curl http://localhost/health

# システムステータス確認
sail artisan atsusearch:status

# キューの状態確認
sail queue:work --once
sail horizon:status
```

### 開発チーム連携

#### Sailエイリアス設定（チーム共通）
```bash
# ~/.bashrc または ~/.zshrc に追加
alias sail='[ -f sail ] && sh sail || sh vendor/bin/sail'

# チーム用便利エイリアス
alias as-up='sail up -d'
alias as-down='sail down'
alias as-test='sail test'
alias as-fresh='sail artisan migrate:fresh --seed'
alias as-logs='sail logs -f'
```

#### Git Hooks（品質保持）
```bash
# .git/hooks/pre-commit
#!/bin/sh
sail composer pint --test
sail test
sail npm run type-check
```

## 今後の拡張予定

### 🚀 Phase 1（短期）
1. **PWA対応**: オフライン機能とプッシュ通知
2. **位置情報自動取得**: GPS連携で現在地のWBGT自動表示
3. **お気に入り機能強化**: 複数地点の一括監視

### 🌍 Phase 2（中期）
4. **AtsuSearch Global**: 英語・中国語・韓国語対応
5. **API公開**: 他のアプリやサービスとの連携
6. **詳細分析ダッシュボード**: MySQL時系列データでのトレンド分析

### 🤖 Phase 3（長期）
7. **AtsuSearch AI**: 機械学習による独自WBGT予測
8. **IoT連携**: 温度センサーデータとの統合
9. **自治体連携**: 地域防災システムとの連携

### 📱 マーケティング展開
- **SNS連携**: #AtsuSearch ハッシュタグでの情報拡散
- **メディア連携**: 天気予報サイトへのウィジェット提供
- **企業向けAPI**: 建設業・スポーツ業界への展開

## AtsuSearch + Laravel Sailの利点

### 🚀 開発効率の向上
- **1コマンドセットアップ**: `curl -s "https://laravel.build/atsusearch?with=mysql,redis,mailhog" | bash`
- **環境統一**: チーム全体で同じDocker環境を共有
- **高速開発**: Vite + HMRでリアルタイム更新
- **統合デバッグ**: Xdebug + Telescope + Horizonで包括的な開発支援

### 🛡️ 信頼性の確保
- **Laravel 12**: ゼロブレイキングチェンジで安全なアップグレード
- **MySQL 8.4**: 高パフォーマンスと信頼性を兼ね備えたデータベース
- **Redis統合**: 高速キャッシュとセッション管理
- **自動テスト**: `sail test`でCIを簡単に実現

### 🌍 スケーラビリティ
- **コンテナ化**: Dockerによる水平スケール対応
- **マイクロサービス化**: 将来的なサービス分割に対応
- **国際化対応**: AtsuSearchブランドでグローバル展開準備
- **API設計**: RESTful APIで外部連携やモバイルアプリ対応

### 💡 AtsuSearchの独自性
- **直感的な名前**: 「暑さを検索」で機能が一目瞭然
- **マップベース**: Google Maps統合で使いやすいUI/UX
- **科学的根拠**: 環境省データで信頼性の高いWBGT情報
- **ブランド拡張性**: AtsuSearch Global、AtsuSearch Pro等の展開可能

## 参考資料

### 技術ドキュメント
- [環境省熱中症予防情報サイト](https://www.wbgt.env.go.jp/)
- [Google Maps Platform ドキュメント](https://developers.google.com/maps/documentation)
- [Laravel 12 ドキュメント](https://laravel.com/docs/12.x)
- [Laravel 12 アップグレードガイド](https://laravel.com/docs/12.x/upgrade)
- [Laravel Sail ドキュメント](https://laravel.com/docs/12.x/sail)
- [Vue.js 3 ガイド](https://v3.vuejs.org/guide/)

### AtsuSearch ブランド
- **英文表記**: AtsuSearch
- **ハッシュタグ**: #AtsuSearch #アツサーチ #WBGT
- **ドメイン候補**: atsusearch.com, atsusearch.jp
- **コンセプト**: 暑さを検索、安全を発見

### 開発コマンド（クイックリファレンス）
```bash
# 環境起動
sail up -d

# 開発サーバー
sail npm run dev

# テスト実行
sail test

# データベース接続
sail mysql

# ログ確認
sail logs -f
```

---

**プロジェクト名**: AtsuSearch（アツサーチ）  
**開発チーム**: [チーム名]  
**最終更新**: 2025年7月21日  
**バージョン**: 3.0.0 (Laravel 12 + Sail対応)