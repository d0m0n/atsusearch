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
    private const WBGT_API_URL = 'https://www.wbgt.env.go.jp/est15WG/dl/wbgt_all.csv';

    public function fetchAndStoreWbgtData(): void
    {
        $cacheKey = 'wbgt_last_update';
        $lastUpdate = Cache::get($cacheKey);
        
        if ($lastUpdate && now()->diffInMinutes($lastUpdate) < 60) {
            Log::info('WBGT data update skipped - recent update found');
            return;
        }

        try {
            // 環境省のデモデータとしてサンプルデータを生成
            $this->generateSampleWbgtData();
            
            Cache::put($cacheKey, now(), self::CACHE_TTL);
            Log::info('WBGT data updated successfully');
        } catch (\Exception $e) {
            Log::error('WBGT data update failed: ' . $e->getMessage());
            throw $e;
        }
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

    public function getLocationWbgt(int $locationId, string $date = null, string $type = 'forecast'): array
    {
        $location = Location::findOrFail($locationId);
        $targetDate = $date ? \Carbon\Carbon::parse($date) : now();

        $wbgtData = WbgtData::where('location_id', $locationId)
            ->where('date', $targetDate->toDateString())
            ->where('data_type', $type)
            ->orderBy('hour')
            ->get();

        return [
            'location' => $location,
            'date' => $targetDate->toDateString(),
            'type' => $type,
            'wbgt_data' => $wbgtData
        ];
    }

    public function searchLocationsByCoordinates(float $latitude, float $longitude, int $radius = 50): \Illuminate\Database\Eloquent\Collection
    {
        return Location::findNearby($latitude, $longitude, $radius);
    }

    public function createLocationFromCoordinates(float $latitude, float $longitude, int $userId = null): Location
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