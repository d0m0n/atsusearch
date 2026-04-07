<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\NearestStationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * 逆ジオコーディングAPIコントローラー。
 *
 * Google Maps Geocoding API を使用して座標→住所変換を行う。
 * APIキーが未設定の場合は座標テキストをそのまま返す。
 */
class GeocodingController extends Controller
{
    private string $apiKey;

    public function __construct(
        private readonly NearestStationService $nearestStationService
    ) {
        $this->apiKey = config('services.google_maps.key', '');
    }

    /**
     * POST /api/geocoding/reverse
     * 緯度経度から住所・地点名を取得
     */
    public function reverse(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'latitude'  => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $lat = (float) $request->input('latitude');
        $lng = (float) $request->input('longitude');

        $name    = $this->reverseGeocode($lat, $lng);
        $station = $this->nearestStationService->findNearest($lat, $lng);

        return response()->json([
            'name'            => $name,
            'address'         => $name,
            'latitude'        => $lat,
            'longitude'       => $lng,
            'nearest_station' => $station,
        ]);
    }

    /**
     * GET /api/stations/nearest?lat={lat}&lng={lng}
     * 最寄りWBGT観測地点を返す
     */
    public function nearestStation(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $station = $this->nearestStationService->findNearest(
            (float) $request->input('lat'),
            (float) $request->input('lng')
        );

        if ($station === null) {
            return response()->json(['message' => '最寄り観測地点が見つかりません'], 404);
        }

        return response()->json(['station' => $station]);
    }

    /**
     * Google Maps Geocoding API で逆ジオコーディングを実行。
     * APIキー未設定またはエラー時は座標テキストを返す。
     */
    private function reverseGeocode(float $lat, float $lng): string
    {
        $fallback = "緯度{$lat}, 経度{$lng}";

        if (empty($this->apiKey)) {
            return $fallback;
        }

        try {
            $response = Http::timeout(5)->get('https://maps.googleapis.com/maps/api/geocode/json', [
                'latlng'   => "{$lat},{$lng}",
                'key'      => $this->apiKey,
                'language' => 'ja',
            ]);

            if (!$response->successful()) {
                return $fallback;
            }

            $results = $response->json('results');
            if (empty($results)) {
                return $fallback;
            }

            // 市区町村レベル（locality）を優先、なければ formatted_address を使用
            $address = $results[0]['formatted_address'] ?? $fallback;

            // "日本、" プレフィックスを除去
            return preg_replace('/^日本、\s*〒?\d*\s*/', '', $address) ?: $address;
        } catch (\Exception $e) {
            Log::warning('GeocodingController: reverse geocode failed — ' . $e->getMessage());
            return $fallback;
        }
    }
}
