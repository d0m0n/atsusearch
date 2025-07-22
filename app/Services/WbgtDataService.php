<?php

namespace App\Services;

use App\Models\Location;
use App\Models\WbgtData;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class WbgtDataService
{
    private const CACHE_TTL = 3600; // 1時間
    private const WBGT_BASE_URL = 'https://www.wbgt.env.go.jp/mntr';

    public function fetchAndStoreWbgtData(): void
    {
        $cacheKey = 'wbgt_last_update';
        $lastUpdate = Cache::get($cacheKey);
        
        if ($lastUpdate && now()->diffInMinutes($lastUpdate) < 60) {
            Log::info('WBGT data update skipped - recent update found');
            return;
        }

        try {
            // 環境省の公式CSV データを取得
            $this->fetchRealWbgtData();
            
            Cache::put($cacheKey, now(), self::CACHE_TTL);
            Log::info('WBGT data updated successfully from Environment Ministry');
        } catch (\Exception $e) {
            Log::error('WBGT data update failed: ' . $e->getMessage());
            // フォールバックとしてサンプルデータを使用
            $this->generateSampleWbgtData();
            Log::info('Using sample WBGT data as fallback');
        }
    }

    /**
     * 環境省の公式WBGTデータを取得
     */
    private function fetchRealWbgtData(): void
    {
        $currentMonth = now()->format('Ym');
        $stations = $this->getOfficialWbgtStationIds();
        
        foreach ($stations as $stationData) {
            try {
                $csvData = $this->fetchStationCsvData($stationData['station_name'], $currentMonth);
                if ($csvData) {
                    $this->processWbgtCsvData($csvData, $stationData);
                }
            } catch (\Exception $e) {
                Log::warning("Failed to fetch WBGT data for station {$stationData['station_name']}: " . $e->getMessage());
                continue;
            }
        }
    }
    
    /**
     * 環境省の観測地点名（dlパス用）とアプリ内地点のマッピング
     */
    private function getOfficialWbgtStationIds(): array
    {
        return [
            // 環境省のCSVファイル名に基づく（実際にアクセス可能な地点）
            ['station_name' => 'Utsunomiya', 'location_name' => '宇都宮', 'lat' => 36.566, 'lon' => 139.883],
            ['station_name' => 'Tokyo', 'location_name' => '東京', 'lat' => 35.681, 'lon' => 139.767],
            ['station_name' => 'Osaka', 'location_name' => '大阪', 'lat' => 34.686, 'lon' => 135.520],
            ['station_name' => 'Nagoya', 'location_name' => '名古屋', 'lat' => 35.181, 'lon' => 136.907],
            ['station_name' => 'Fukuoka', 'location_name' => '福岡', 'lat' => 33.606, 'lon' => 130.418],
            ['station_name' => 'Sendai', 'location_name' => '仙台', 'lat' => 38.268, 'lon' => 140.872],
            ['station_name' => 'Sapporo', 'location_name' => '札幌', 'lat' => 43.064, 'lon' => 141.347],
            ['station_name' => 'Hiroshima', 'location_name' => '広島', 'lat' => 34.397, 'lon' => 132.460],
            ['station_name' => 'Shizuoka', 'location_name' => '静岡', 'lat' => 34.976, 'lon' => 138.383],
            ['station_name' => 'Kumamoto', 'location_name' => '熊本', 'lat' => 32.790, 'lon' => 130.742],
        ];
    }
    
    /**
     * 特定観測地点のCSVデータを取得
     */
    private function fetchStationCsvData(string $stationName, string $yearMonth): ?string
    {
        $url = self::WBGT_BASE_URL . "/dl/{$stationName}_{$yearMonth}.csv";
        
        try {
            Log::info("Fetching WBGT CSV from: {$url}");
            $response = Http::timeout(30)->get($url);
            
            if (!$response->successful()) {
                Log::warning("Failed to fetch WBGT CSV from {$url}: " . $response->status());
                return null;
            }
            
            return $response->body();
        } catch (\Exception $e) {
            Log::error("Error fetching WBGT CSV from {$url}: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * 環境省CSVデータを処理してデータベースに保存
     */
    private function processWbgtCsvData(string $csvData, array $stationData): void
    {
        $lines = explode("\n", trim($csvData));
        
        // ヘッダー行をスキップ（CSV形式によって調整が必要）
        if (count($lines) > 1) {
            array_shift($lines);
        }
        
        // 対応するLocationレコードを取得または作成
        $location = Location::firstOrCreate([
            'name' => $stationData['location_name'],
            'latitude' => $stationData['lat'],
            'longitude' => $stationData['lon']
        ], [
            'address' => $stationData['location_name'],
            'prefecture_code' => $this->getPrefectureCode($stationData['location_name']),
            'user_id' => null
        ]);
        
        foreach ($lines as $line) {
            if (empty(trim($line))) continue;
            
            $data = str_getcsv($line);
            if (count($data) >= 3) {
                try {
                    // CSV形式: Date,Time,WBGT,Tg (推定)
                    $dateStr = $data[0] ?? '';
                    $timeStr = $data[1] ?? '';
                    $wbgtValue = isset($data[2]) && is_numeric($data[2]) ? (float)$data[2] : null;
                    
                    if ($wbgtValue !== null && $dateStr && $timeStr) {
                        // 日時をパース
                        $dateTime = $this->parseDateTime($dateStr, $timeStr);
                        if ($dateTime) {
                            WbgtData::updateOrCreate([
                                'location_id' => $location->id,
                                'date' => $dateTime->toDateString(),
                                'hour' => $dateTime->hour,
                                'data_type' => 'actual'
                            ], [
                                'wbgt_value' => $wbgtValue
                            ]);
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning("Failed to process CSV line: {$line} - " . $e->getMessage());
                    continue;
                }
            }
        }
    }
    
    /**
     * 日時文字列をパース
     */
    private function parseDateTime(string $dateStr, string $timeStr): ?\Carbon\Carbon
    {
        try {
            // 環境省CSV形式に応じて調整が必要
            // 例: "2025/5/1" と "14:00" のような形式を想定
            $dateStr = str_replace('/', '-', $dateStr);
            $hour = (int)substr($timeStr, 0, 2);
            
            return \Carbon\Carbon::createFromFormat('Y-n-j H:i', $dateStr . ' ' . sprintf('%02d:00', $hour));
        } catch (\Exception $e) {
            Log::warning("Failed to parse date/time: {$dateStr} {$timeStr} - " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * 地名から都道府県コードを取得
     */
    private function getPrefectureCode(string $locationName): string
    {
        $prefectureCodes = [
            '札幌' => '01', '青森' => '02', '盛岡' => '03', '仙台' => '04', '秋田' => '05',
            '山形' => '06', '福島' => '07', '水戸' => '08', '宇都宮' => '09', '前橋' => '10',
            '熊谷' => '11', '東京' => '13', '銚子' => '12', '横浜' => '14', '新潟' => '15',
            '富山' => '16', '金沢' => '17', '福井' => '18', '甲府' => '19', '長野' => '20',
            '岐阜' => '21', '静岡' => '22', '名古屋' => '23', '津' => '24', '彦根' => '25',
            '京都' => '26', '大阪' => '27', '神戸' => '28', '奈良' => '29', '和歌山' => '30',
            '鳥取' => '31', '松江' => '32', '岡山' => '33', '広島' => '34', '下関' => '35',
            '徳島' => '36', '高松' => '37', '松山' => '38', '高知' => '39', '福岡' => '40',
            '佐賀' => '41', '長崎' => '42', '熊本' => '43', '大分' => '44', '宮崎' => '45',
            '鹿児島' => '46', '那覇' => '47'
        ];
        
        return $prefectureCodes[$locationName] ?? '13'; // デフォルトは東京
    }

    private function generateSampleWbgtData(): void
    {
        // 主要都市のサンプル地点を作成
        $sampleLocations = [
            ['name' => '東京', 'latitude' => 35.6812, 'longitude' => 139.7671, 'prefecture_code' => '13'],
            ['name' => '横浜', 'latitude' => 35.4437, 'longitude' => 139.6380, 'prefecture_code' => '14'],
            ['name' => '大阪', 'latitude' => 34.6937, 'longitude' => 135.5023, 'prefecture_code' => '27'],
            ['name' => '名古屋', 'latitude' => 35.1815, 'longitude' => 136.9066, 'prefecture_code' => '23'],
            ['name' => '福岡', 'latitude' => 33.5904, 'longitude' => 130.4017, 'prefecture_code' => '40'],
        ];

        foreach ($sampleLocations as $locationData) {
            $location = Location::firstOrCreate([
                'name' => $locationData['name'],
                'latitude' => $locationData['latitude'],
                'longitude' => $locationData['longitude']
            ], [
                'address' => $locationData['name'],
                'prefecture_code' => $locationData['prefecture_code'],
                'user_id' => null
            ]);

            $this->generateHourlyWbgtData($location);
        }
    }

    private function generateHourlyWbgtData(Location $location): void
    {
        $today = now()->startOfDay();
        $tomorrow = $today->copy()->addDay();

        // 今日と明日のデータを生成
        foreach ([$today, $tomorrow] as $date) {
            for ($hour = 0; $hour < 24; $hour++) {
                // 実況データ（今日のみ、現在時刻まで）
                if ($date->isToday() && $hour <= now()->hour) {
                    WbgtData::updateOrCreate([
                        'location_id' => $location->id,
                        'date' => $date->toDateString(),
                        'hour' => $hour,
                        'data_type' => 'actual'
                    ], [
                        'wbgt_value' => $this->generateWbgtValue($hour)
                    ]);
                }

                // 予測データ
                if ($date->isFuture() || ($date->isToday() && $hour > now()->hour)) {
                    WbgtData::updateOrCreate([
                        'location_id' => $location->id,
                        'date' => $date->toDateString(),
                        'hour' => $hour,
                        'data_type' => 'forecast'
                    ], [
                        'wbgt_value' => $this->generateWbgtValue($hour, 2) // 予測は少し高めに
                    ]);
                }
            }
        }
    }

    private function generateWbgtValue(int $hour, float $offset = 0): float
    {
        // 時間帯に応じた現実的なWBGT値を生成
        $baseValue = match(true) {
            $hour >= 0 && $hour < 6 => 18 + $offset, // 深夜〜早朝
            $hour >= 6 && $hour < 9 => 22 + $offset, // 朝
            $hour >= 9 && $hour < 12 => 26 + $offset, // 午前
            $hour >= 12 && $hour < 15 => 30 + $offset, // 昼
            $hour >= 15 && $hour < 18 => 28 + $offset, // 午後
            $hour >= 18 && $hour < 21 => 24 + $offset, // 夕方
            default => 20 + $offset // 夜
        };

        // ランダム要素を追加（±2度）
        $randomOffset = (mt_rand(-200, 200) / 100);
        
        return round($baseValue + $randomOffset, 1);
    }

    /**
     * WBGT観測地点情報を取得
     */
    private function getWbgtObservationStation(float $latitude, float $longitude): array
    {
        // 環境省の実測WBGT観測地点データ（47都道府県各1地点）
        // 2025年度暑さ指数(WBGT)情報提供地点に基づく気象台観測地点
        $wbgtStations = [
            // 北海道・東北地方
            ['id' => '47412', 'name' => '札幌', 'display_name' => '札幌', 'lat' => 43.064, 'lon' => 141.347],
            ['id' => '47575', 'name' => '青森', 'display_name' => '青森', 'lat' => 40.824, 'lon' => 140.740],
            ['id' => '47581', 'name' => '盛岡', 'display_name' => '盛岡', 'lat' => 39.704, 'lon' => 141.153],
            ['id' => '47590', 'name' => '仙台', 'display_name' => '仙台', 'lat' => 38.268, 'lon' => 140.872],
            ['id' => '47582', 'name' => '秋田', 'display_name' => '秋田', 'lat' => 39.719, 'lon' => 140.102],
            ['id' => '47588', 'name' => '山形', 'display_name' => '山形', 'lat' => 38.253, 'lon' => 140.339],
            ['id' => '47595', 'name' => '福島', 'display_name' => '福島', 'lat' => 37.750, 'lon' => 140.468],
            
            // 関東地方
            ['id' => '47629', 'name' => '水戸', 'display_name' => '水戸', 'lat' => 36.342, 'lon' => 140.447],
            ['id' => '47615', 'name' => '宇都宮', 'display_name' => '宇都宮', 'lat' => 36.566, 'lon' => 139.883],
            ['id' => '47624', 'name' => '前橋', 'display_name' => '前橋', 'lat' => 36.391, 'lon' => 139.061],
            ['id' => '47626', 'name' => '熊谷', 'display_name' => '熊谷', 'lat' => 36.148, 'lon' => 139.389],
            ['id' => '47662', 'name' => '東京', 'display_name' => '東京', 'lat' => 35.681, 'lon' => 139.767],
            ['id' => '47648', 'name' => '銚子', 'display_name' => '銚子', 'lat' => 35.735, 'lon' => 140.847],
            ['id' => '47670', 'name' => '横浜', 'display_name' => '横浜', 'lat' => 35.444, 'lon' => 139.638],
            
            // 中部地方  
            ['id' => '47604', 'name' => '新潟', 'display_name' => '新潟', 'lat' => 37.916, 'lon' => 139.036],
            ['id' => '47607', 'name' => '富山', 'display_name' => '富山', 'lat' => 36.696, 'lon' => 137.213],
            ['id' => '47605', 'name' => '金沢', 'display_name' => '金沢', 'lat' => 36.595, 'lon' => 136.626],
            ['id' => '47616', 'name' => '福井', 'display_name' => '福井', 'lat' => 36.065, 'lon' => 136.222],
            ['id' => '47638', 'name' => '甲府', 'display_name' => '甲府', 'lat' => 35.664, 'lon' => 138.569],
            ['id' => '47610', 'name' => '長野', 'display_name' => '長野', 'lat' => 36.651, 'lon' => 138.181],
            ['id' => '47632', 'name' => '岐阜', 'display_name' => '岐阜', 'lat' => 35.391, 'lon' => 136.722],
            ['id' => '47656', 'name' => '静岡', 'display_name' => '静岡', 'lat' => 34.976, 'lon' => 138.383],
            ['id' => '47636', 'name' => '名古屋', 'display_name' => '名古屋', 'lat' => 35.181, 'lon' => 136.907],
            ['id' => '47651', 'name' => '津', 'display_name' => '津', 'lat' => 34.730, 'lon' => 136.508],
            
            // 関西地方
            ['id' => '47761', 'name' => '彦根', 'display_name' => '彦根', 'lat' => 35.276, 'lon' => 136.251],
            ['id' => '47759', 'name' => '京都', 'display_name' => '京都', 'lat' => 35.012, 'lon' => 135.768],
            ['id' => '47772', 'name' => '大阪', 'display_name' => '大阪', 'lat' => 34.686, 'lon' => 135.520],
            ['id' => '47770', 'name' => '神戸', 'display_name' => '神戸', 'lat' => 34.691, 'lon' => 135.183],
            ['id' => '47780', 'name' => '奈良', 'display_name' => '奈良', 'lat' => 34.685, 'lon' => 135.805],
            ['id' => '47777', 'name' => '和歌山', 'display_name' => '和歌山', 'lat' => 34.226, 'lon' => 135.167],
            
            // 中国・四国地方
            ['id' => '47746', 'name' => '鳥取', 'display_name' => '鳥取', 'lat' => 35.504, 'lon' => 134.238],
            ['id' => '47741', 'name' => '松江', 'display_name' => '松江', 'lat' => 35.472, 'lon' => 133.051],
            ['id' => '47768', 'name' => '岡山', 'display_name' => '岡山', 'lat' => 34.662, 'lon' => 133.935],
            ['id' => '47765', 'name' => '広島', 'display_name' => '広島', 'lat' => 34.397, 'lon' => 132.460],
            ['id' => '47750', 'name' => '下関', 'display_name' => '下関', 'lat' => 33.951, 'lon' => 130.925],
            ['id' => '47895', 'name' => '徳島', 'display_name' => '徳島', 'lat' => 34.066, 'lon' => 134.559],
            ['id' => '47891', 'name' => '高松', 'display_name' => '高松', 'lat' => 34.340, 'lon' => 134.043],
            ['id' => '47887', 'name' => '松山', 'display_name' => '松山', 'lat' => 33.842, 'lon' => 132.766],
            ['id' => '47893', 'name' => '高知', 'display_name' => '高知', 'lat' => 33.560, 'lon' => 133.531],
            
            // 九州・沖縄地方
            ['id' => '47807', 'name' => '福岡', 'display_name' => '福岡', 'lat' => 33.606, 'lon' => 130.418],
            ['id' => '47813', 'name' => '佐賀', 'display_name' => '佐賀', 'lat' => 33.263, 'lon' => 130.300],
            ['id' => '47817', 'name' => '長崎', 'display_name' => '長崎', 'lat' => 32.745, 'lon' => 129.874],
            ['id' => '47819', 'name' => '熊本', 'display_name' => '熊本', 'lat' => 32.790, 'lon' => 130.742],
            ['id' => '47815', 'name' => '大分', 'display_name' => '大分', 'lat' => 33.238, 'lon' => 131.613],
            ['id' => '47830', 'name' => '宮崎', 'display_name' => '宮崎', 'lat' => 31.911, 'lon' => 131.424],
            ['id' => '47827', 'name' => '鹿児島', 'display_name' => '鹿児島', 'lat' => 31.597, 'lon' => 130.557],
            ['id' => '47936', 'name' => '那覇', 'display_name' => '那覇', 'lat' => 26.213, 'lon' => 127.679],
        ];

        // 最寄りのWBGT観測地点を検索
        $nearestStation = null;
        $minDistance = PHP_FLOAT_MAX;

        foreach ($wbgtStations as $station) {
            $distance = $this->calculateDistance(
                $latitude, 
                $longitude, 
                $station['lat'], 
                $station['lon']
            );

            if ($distance < $minDistance) {
                $minDistance = $distance;
                $nearestStation = [
                    'id' => $station['id'],
                    'name' => $station['name'],
                    'display_name' => $station['display_name'], 
                    'distance' => round($distance, 2)
                ];
            }
        }

        return $nearestStation;
    }

    /**
     * 2点間の距離を計算（km）
     */
    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // 地球の半径（km）
        
        $lat1Rad = deg2rad($lat1);
        $lat2Rad = deg2rad($lat2);
        $deltaLatRad = deg2rad($lat2 - $lat1);
        $deltaLonRad = deg2rad($lon2 - $lon1);
        
        $a = sin($deltaLatRad / 2) * sin($deltaLatRad / 2) +
             cos($lat1Rad) * cos($lat2Rad) *
             sin($deltaLonRad / 2) * sin($deltaLonRad / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        return $earthRadius * $c;
    }

    public function getLocationWbgt(int $locationId, ?string $date = null, string $type = 'forecast'): array
    {
        $location = Location::findOrFail($locationId);
        $targetDate = $date ? \Carbon\Carbon::parse($date) : now();

        $wbgtData = WbgtData::where('location_id', $locationId)
            ->where('date', $targetDate->toDateString())
            ->where('data_type', $type)
            ->orderBy('hour')
            ->get();

        // WBGT観測地点情報を追加
        $wbgtStation = $this->getWbgtObservationStation($location->latitude, $location->longitude);

        return [
            'location' => $location,
            'date' => $targetDate->toDateString(),
            'type' => $type,
            'wbgt_data' => $wbgtData,
            'wbgt_station' => $wbgtStation
        ];
    }

    public function searchLocationsByCoordinates(float $latitude, float $longitude, int $radius = 50): \Illuminate\Database\Eloquent\Collection
    {
        return Location::findNearby($latitude, $longitude, $radius);
    }

    public function createLocationFromCoordinates(float $latitude, float $longitude, ?int $userId = null): Location
    {
        // 逆ジオコーディングのモック（実際にはGoogle Maps APIを使用）
        $mockAddress = "緯度: {$latitude}, 経度: {$longitude}";
        $mockName = "地点_" . substr(md5($latitude . $longitude), 0, 8);

        $location = Location::create([
            'name' => $mockName,
            'address' => $mockAddress,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'prefecture_code' => '13', // デモ用に東京に固定
            'user_id' => $userId,
            'is_favorite' => false
        ]);

        // 新しい地点のWBGTデータを生成
        $this->generateHourlyWbgtData($location);

        return $location;
    }
}