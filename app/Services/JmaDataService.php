<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class JmaDataService
{
    private const BASE_URL = 'https://www.jma.go.jp/bosai/amedas';
    private const CACHE_TTL = 600; // 10分間キャッシュ
    
    /**
     * 最寄りのアメダス観測地点を取得
     */
    public function getNearestStation(float $latitude, float $longitude): ?array
    {
        $stations = $this->getAmedasStations();
        if (!$stations) {
            return null;
        }
        
        $nearestStation = null;
        $minDistance = PHP_FLOAT_MAX;
        
        foreach ($stations as $stationId => $station) {
            if (!isset($station['lat']) || !isset($station['lon'])) {
                continue;
            }
            
            $distance = $this->calculateDistance(
                $latitude, 
                $longitude, 
                $station['lat'][0] + $station['lat'][1] / 60, // 度分を度に変換
                $station['lon'][0] + $station['lon'][1] / 60
            );
            
            if ($distance < $minDistance) {
                $minDistance = $distance;
                $nearestStation = [
                    'id' => $stationId,
                    'name' => $station['kjName'],
                    'distance' => round($distance, 2)
                ];
            }
        }
        
        return $nearestStation;
    }
    
    /**
     * 指定した観測地点の最新気温データを取得
     */
    public function getTemperatureData(string $stationId): ?array
    {
        $cacheKey = "jma_temp_{$stationId}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($stationId) {
            try {
                $now = Carbon::now('Asia/Tokyo');
                $dateTime = $now->format('Ymd');
                
                // 3時間刻みの観測時刻を計算（00, 03, 06, 09, 12, 15, 18, 21）
                $hour = intval($now->hour / 3) * 3;
                $timeSlot = sprintf('%02d', $hour);
                
                $url = self::BASE_URL . "/data/point/{$stationId}/{$dateTime}_{$timeSlot}.json";
                
                Log::info("Trying JMA URL: " . $url);
                $response = Http::timeout(10)->get($url);
                
                if (!$response->successful()) {
                    Log::warning("First attempt failed: " . $response->status());
                    
                    // 前の3時間スロットを試行
                    $prevHour = $hour - 3;
                    if ($prevHour < 0) {
                        $prevHour = 21; // 前日の21時
                        $prevDateTime = $now->subDay()->format('Ymd');
                    } else {
                        $prevDateTime = $dateTime;
                    }
                    
                    $prevTimeSlot = sprintf('%02d', $prevHour);
                    $url = self::BASE_URL . "/data/point/{$stationId}/{$prevDateTime}_{$prevTimeSlot}.json";
                    Log::info("Trying fallback URL: " . $url);
                    $response = Http::timeout(10)->get($url);
                }
                
                if (!$response->successful()) {
                    Log::warning("JMA API failed for station {$stationId}: " . $response->status());
                    Log::warning("Final URL attempted: " . $url);
                    return null;
                }
                
                $data = $response->json();
                Log::info("JMA API response data for station {$stationId}: " . json_encode($data));
                
                return $this->parseTemperatureData($data);
                
            } catch (\Exception $e) {
                Log::error("JMA API error for station {$stationId}: " . $e->getMessage());
                return null;
            }
        });
    }
    
    /**
     * アメダス観測地点一覧を取得
     */
    private function getAmedasStations(): ?array
    {
        return Cache::remember('amedas_stations', 3600 * 24, function () {
            try {
                $response = Http::timeout(10)->get(self::BASE_URL . '/const/amedastable.json');
                
                if (!$response->successful()) {
                    Log::error('Failed to fetch AMeDAS stations: ' . $response->status());
                    return null;
                }
                
                return $response->json();
                
            } catch (\Exception $e) {
                Log::error('AMeDAS stations fetch error: ' . $e->getMessage());
                return null;
            }
        });
    }
    
    /**
     * 気温データを解析
     */
    private function parseTemperatureData(array $data): ?array
    {
        Log::info("Parsing temperature data: " . json_encode($data));
        
        // 最新のタイムスタンプを取得
        $timestamps = array_keys($data);
        if (empty($timestamps)) {
            Log::warning("No timestamp data found");
            return null;
        }
        
        // 最新のタイムスタンプのデータを取得
        $latestTimestamp = end($timestamps);
        $latestData = $data[$latestTimestamp];
        
        Log::info("Latest timestamp: {$latestTimestamp}");
        Log::info("Latest data: " . json_encode($latestData));
        
        // 気温データが存在するかチェック
        if (!isset($latestData['temp']) || !is_array($latestData['temp'])) {
            Log::warning("No temp data found or temp is not array. Keys: " . implode(', ', array_keys($latestData)));
            return null;
        }
        
        $temperature = $latestData['temp'][0] ?? null;
        if ($temperature === null) {
            Log::warning("Temperature value is null in temp array: " . json_encode($latestData['temp']));
            return null;
        }
        
        Log::info("Successfully parsed temperature: " . $temperature);
        
        // タイムスタンプをフォーマット
        $observationTime = \DateTime::createFromFormat('YmdHis', $latestTimestamp);
        
        return [
            'temperature' => round($temperature, 1),
            'observation_time' => $observationTime ? $observationTime->format('Y-m-d H:i:s') : $latestTimestamp,
            'data_source' => '気象庁アメダス',
            'data_source_url' => 'https://www.data.jma.go.jp/stats/data/mdrr/index.html'
        ];
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
}