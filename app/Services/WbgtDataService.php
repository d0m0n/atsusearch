<?php

namespace App\Services;

use App\Models\Location;
use App\Models\WbgtData;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * 環境省WBGTデータの取得・保存・照会を担うサービス。
 *
 * CSV取得・解析は WbgtCsvParser に委譲。
 * 最寄り観測地点の特定は NearestStationService に委譲。
 */
class WbgtDataService
{
    private const CACHE_KEY_LAST_UPDATE = 'wbgt_last_update';
    private const CACHE_TTL_SECONDS     = 3600; // 1時間
    private const UPDATE_INTERVAL_MIN   = 60;   // 最小更新間隔（分）

    /** 環境省WBGT CSV基底URL（実況値） */
    private string $baseUrl;

    /** 環境省WBGT 予測値CSV基底URL */
    private string $forecastUrl;

    public function __construct(
        private readonly WbgtCsvParser        $csvParser,
        private readonly NearestStationService $nearestStationService
    ) {
        $this->baseUrl     = config('services.wbgt.base_url',     'https://www.wbgt.env.go.jp/prev15WG/dl/');
        $this->forecastUrl = config('services.wbgt.forecast_url', 'https://www.wbgt.env.go.jp/prev15WG/dl/');
    }

    // =========================================================
    // 公開API
    // =========================================================

    /**
     * 環境省からWBGTデータを取得してDBに保存する。
     * 最終更新から CACHE_TTL_SECONDS 以内はスキップ（--force で上書き可）。
     */
    public function fetchAndStoreWbgtData(): void
    {
        $lastUpdate = Cache::get(self::CACHE_KEY_LAST_UPDATE);

        if ($lastUpdate && now()->diffInMinutes($lastUpdate) < self::UPDATE_INTERVAL_MIN) {
            Log::info('WbgtDataService: skipping update (within interval)');
            return;
        }

        try {
            $this->fetchForecastCsv();
            Cache::put(self::CACHE_KEY_LAST_UPDATE, now(), self::CACHE_TTL_SECONDS);
            Log::info('WbgtDataService: WBGT data updated successfully');
        } catch (\Exception $e) {
            Log::error('WbgtDataService: fetch failed — ' . $e->getMessage());
            // フォールバック: サンプルデータで主要都市を補完
            $this->generateSampleWbgtData();
            Log::info('WbgtDataService: using sample WBGT data as fallback');
        }
    }

    /**
     * 指定 location_id のWBGTデータを返す。
     *
     * @return array{location: Location, date: string, type: string, wbgt_data: \Illuminate\Database\Eloquent\Collection, wbgt_station: array|null}
     */
    public function getLocationWbgt(int $locationId, ?string $date = null, string $type = 'forecast'): array
    {
        $location   = Location::findOrFail($locationId);
        $targetDate = $date ? \Carbon\Carbon::parse($date) : now('Asia/Tokyo');
        $currentHour = now('Asia/Tokyo')->hour;

        $wbgtData = WbgtData::where('location_id', $locationId)
            ->where('date', $targetDate->toDateString())
            ->when($type === 'actual', fn ($q) =>
                $q->where('data_type', 'actual')->where('hour', '<=', $currentHour)->orderBy('hour', 'desc')
            )
            ->when($type === 'forecast', fn ($q) =>
                $q->where(fn ($inner) =>
                    $inner->where('data_type', 'actual')->where('hour', '<=', $currentHour)
                )->orWhere(fn ($inner) =>
                    $inner->where('data_type', 'forecast')->where('hour', '>', $currentHour)
                )
            )
            ->orderBy('hour')
            ->get();

        // データが古い or 空の場合は更新を試みる
        $latestData = $wbgtData->sortByDesc('hour')->first();
        if (!$latestData || $latestData->updated_at->lt(now()->subHours(3))) {
            Log::info("WbgtDataService: data stale for location {$locationId}, refreshing");
            $this->fetchAndStoreWbgtData();
            $wbgtData = WbgtData::where('location_id', $locationId)
                ->where('date', $targetDate->toDateString())
                ->orderBy('hour')
                ->get();
        }

        $wbgtStation = $this->nearestStationService->findNearest($location->latitude, $location->longitude);

        return [
            'location'     => $location,
            'date'         => $targetDate->toDateString(),
            'type'         => $type,
            'wbgt_data'    => $wbgtData,
            'wbgt_station' => $wbgtStation,
        ];
    }

    /**
     * 近隣の Location を返す（半径 km 以内）。
     */
    public function searchLocationsByCoordinates(float $latitude, float $longitude, int $radius = 50): \Illuminate\Database\Eloquent\Collection
    {
        return Location::findNearby($latitude, $longitude, $radius);
    }

    /**
     * 座標から Location を作成してサンプルWBGTデータを付与する。
     */
    public function createLocationFromCoordinates(float $latitude, float $longitude, ?int $userId = null): Location
    {
        $location = Location::create([
            'name'            => "地点_" . substr(md5($latitude . $longitude), 0, 6),
            'address'         => "緯度: {$latitude}, 経度: {$longitude}",
            'latitude'        => $latitude,
            'longitude'       => $longitude,
            'prefecture_code' => $this->nearestStationService->findNearest($latitude, $longitude)['prefecture_code'] ?? '13',
            'user_id'         => $userId,
            'is_favorite'     => false,
        ]);

        $this->generateHourlyWbgtData($location);

        return $location;
    }

    // =========================================================
    // 内部処理
    // =========================================================

    /**
     * 予測値CSV（yohou_all.csv）を取得してDBに保存する。
     */
    private function fetchForecastCsv(): void
    {
        $url = rtrim($this->forecastUrl, '/') . '/yohou_all.csv';
        Log::info("WbgtDataService: fetching forecast CSV from {$url}");

        $response = Http::timeout(30)->get($url);

        if (!$response->successful()) {
            throw new \RuntimeException("Failed to fetch forecast CSV: HTTP {$response->status()}");
        }

        $stations = $this->nearestStationService->getAllStations();
        $successCount = 0;

        foreach ($stations as $station) {
            $records = $this->csvParser->parseForecastCsv($response->body(), $station['id']);
            if (empty($records)) continue;

            $location = $this->findOrCreateStationLocation($station);

            foreach ($records as $record) {
                WbgtData::updateOrCreate(
                    [
                        'location_id' => $location->id,
                        'date'        => $record['datetime']->toDateString(),
                        'hour'        => $record['datetime']->hour,
                        'data_type'   => 'forecast',
                    ],
                    [
                        'wbgt_value'  => $record['wbgt_value'],
                        'data_source' => 'csv',
                        'fetch_time'  => now(),
                    ]
                );
            }
            $successCount++;
        }

        Log::info("WbgtDataService: saved forecast data for {$successCount} stations");
    }

    /**
     * 実況値CSV（月次）を取得してDBに保存する。
     */
    private function fetchActualCsv(string $stationId, string $yearMonth): void
    {
        $url = rtrim($this->baseUrl, '/') . "/wbgt_{$stationId}_{$yearMonth}.csv";

        $response = Http::timeout(30)->get($url);
        if (!$response->successful()) {
            Log::warning("WbgtDataService: actual CSV not available for station {$stationId} ({$response->status()})");
            return;
        }

        $records = $this->csvParser->parseActualCsv($response->body());
        if (empty($records)) return;

        $station  = $this->nearestStationService->findById($stationId);
        if ($station === null) return;

        $location = $this->findOrCreateStationLocation($station);

        foreach ($records as $record) {
            WbgtData::updateOrCreate(
                [
                    'location_id' => $location->id,
                    'date'        => $record['datetime']->toDateString(),
                    'hour'        => $record['datetime']->hour,
                    'data_type'   => 'actual',
                ],
                [
                    'wbgt_value'  => $record['wbgt_value'],
                    'data_source' => 'csv',
                    'fetch_time'  => now(),
                ]
            );
        }
    }

    /**
     * 観測地点に対応する Location を取得または作成する。
     *
     * @param  array{id: string, name: string, lat: float, lon: float, prefecture_code: string} $station
     */
    private function findOrCreateStationLocation(array $station): Location
    {
        return Location::firstOrCreate(
            ['name' => $station['name'], 'latitude' => $station['lat'], 'longitude' => $station['lon']],
            [
                'address'         => $station['name'],
                'prefecture_code' => $station['prefecture_code'],
                'user_id'         => null,
            ]
        );
    }

    // =========================================================
    // サンプルデータ生成（フォールバック / テスト用）
    // =========================================================

    private function generateSampleWbgtData(): void
    {
        $sampleLocations = [
            ['name' => '東京',  'lat' => 35.6812, 'lon' => 139.7671, 'prefecture_code' => '13'],
            ['name' => '大阪',  'lat' => 34.6937, 'lon' => 135.5023, 'prefecture_code' => '27'],
            ['name' => '名古屋','lat' => 35.1815, 'lon' => 136.9066, 'prefecture_code' => '23'],
            ['name' => '福岡',  'lat' => 33.5904, 'lon' => 130.4017, 'prefecture_code' => '40'],
            ['name' => '札幌',  'lat' => 43.0642, 'lon' => 141.3469, 'prefecture_code' => '01'],
        ];

        foreach ($sampleLocations as $data) {
            $location = Location::firstOrCreate(
                ['name' => $data['name'], 'latitude' => $data['lat'], 'longitude' => $data['lon']],
                ['address' => $data['name'], 'prefecture_code' => $data['prefecture_code'], 'user_id' => null]
            );
            $this->generateHourlyWbgtData($location);
        }
    }

    private function generateHourlyWbgtData(Location $location): void
    {
        $today    = now('Asia/Tokyo')->startOfDay();
        $tomorrow = $today->copy()->addDay();
        $nowHour  = now('Asia/Tokyo')->hour;

        foreach ([$today, $tomorrow] as $date) {
            for ($hour = 0; $hour < 24; $hour++) {
                $isToday  = $date->isToday();
                $isPast   = $isToday && $hour <= $nowHour;
                $isFuture = !$isToday || $hour > $nowHour;

                if ($isPast) {
                    WbgtData::updateOrCreate(
                        ['location_id' => $location->id, 'date' => $date->toDateString(), 'hour' => $hour, 'data_type' => 'actual'],
                        ['wbgt_value' => $this->sampleWbgt($hour), 'data_source' => 'sample', 'fetch_time' => now()]
                    );
                }
                if ($isFuture) {
                    WbgtData::updateOrCreate(
                        ['location_id' => $location->id, 'date' => $date->toDateString(), 'hour' => $hour, 'data_type' => 'forecast'],
                        ['wbgt_value' => $this->sampleWbgt($hour, 2.0), 'data_source' => 'sample', 'fetch_time' => now()]
                    );
                }
            }
        }
    }

    private function sampleWbgt(int $hour, float $offset = 0.0): float
    {
        $base = match (true) {
            $hour < 6           => 18,
            $hour < 9           => 22,
            $hour < 12          => 26,
            $hour < 15          => 30,
            $hour < 18          => 28,
            $hour < 21          => 24,
            default             => 20,
        };

        return round($base + $offset + (mt_rand(-200, 200) / 100), 1);
    }
}
