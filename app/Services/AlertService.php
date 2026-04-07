<?php

namespace App\Services;

use App\Models\HeatAlert;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * 熱中症警戒アラートの取得・管理サービス。
 *
 * 環境省アラートデータURL: https://www.wbgt.env.go.jp/alert_data/
 * ファイル名: alert_YYYYMMDD.json （日次更新）
 *
 * 運用期間: 毎年4月第4水曜日〜10月22日頃
 */
class AlertService
{
    private const CACHE_KEY_PREFIX = 'heat_alert_';
    private const CACHE_TTL        = 1800; // 30分

    private string $alertBaseUrl;

    public function __construct()
    {
        $this->alertBaseUrl = config('services.wbgt.alert_url', 'https://www.wbgt.env.go.jp/alert_data/');
    }

    /**
     * 指定都道府県の今日有効なアラートを返す。
     *
     * @param  string|null $prefectureCode 都道府県コード（null=全都道府県）
     * @return HeatAlert[]|\Illuminate\Database\Eloquent\Collection
     */
    public function getActiveAlerts(?string $prefectureCode = null)
    {
        $query = HeatAlert::where('is_active', true)
            ->where('target_date', now('Asia/Tokyo')->toDateString());

        if ($prefectureCode !== null) {
            $query->where('prefecture_code', $prefectureCode);
        }

        return $query->orderBy('prefecture_code')->get();
    }

    /**
     * 環境省からアラートデータを取得してDBを更新する。
     * キャッシュが有効な場合は通信をスキップする。
     */
    public function syncFromEnvironmentMinistry(): void
    {
        $today    = now('Asia/Tokyo')->toDateString();
        $cacheKey = self::CACHE_KEY_PREFIX . $today;

        if (Cache::has($cacheKey)) {
            Log::info('AlertService: cache hit, skipping sync.');
            return;
        }

        try {
            $filename = 'alert_' . now('Asia/Tokyo')->format('Ymd') . '.json';
            $url      = rtrim($this->alertBaseUrl, '/') . '/' . $filename;

            $response = Http::timeout(15)->get($url);

            if (!$response->successful()) {
                Log::warning("AlertService: failed to fetch alert data ({$response->status()}) from {$url}");
                return;
            }

            $data = $response->json();
            $this->processAlertData($data, $today);

            Cache::put($cacheKey, true, self::CACHE_TTL);
            Log::info("AlertService: synced alert data for {$today}");
        } catch (\Exception $e) {
            Log::error('AlertService: sync failed — ' . $e->getMessage());
        }
    }

    /**
     * アラートJSONを解析してDBに保存・更新する。
     *
     * 環境省JSONの想定フォーマット:
     * {
     *   "target_date": "20250801",
     *   "alerts": [
     *     { "prefecture_code": "13", "alert_type": "warning", "issued_at": "..." },
     *     ...
     *   ]
     * }
     */
    private function processAlertData(array $data, string $targetDate): void
    {
        // 当日の既存アラートを非アクティブ化
        HeatAlert::where('target_date', $targetDate)->update(['is_active' => false]);

        $alerts = $data['alerts'] ?? [];

        foreach ($alerts as $alert) {
            $prefCode = $alert['prefecture_code'] ?? null;
            if ($prefCode === null) continue;

            HeatAlert::updateOrCreate(
                [
                    'prefecture_code' => $prefCode,
                    'target_date'     => $targetDate,
                    'alert_type'      => $alert['alert_type'] ?? 'warning',
                ],
                [
                    'issued_at' => isset($alert['issued_at'])
                        ? \Carbon\Carbon::parse($alert['issued_at'])
                        : now(),
                    'is_active' => true,
                ]
            );
        }
    }
}
