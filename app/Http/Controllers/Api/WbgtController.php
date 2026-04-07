<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WbgtData;
use App\Services\WbgtDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WbgtController extends Controller
{
    public function __construct(
        private readonly WbgtDataService $wbgtService
    ) {}

    /**
     * GET /api/wbgt?lat={lat}&lng={lng}
     * GET /api/wbgt?address={address}
     * 座標または住所からWBGTを取得
     */
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'lat'     => 'required_without:address|numeric|between:-90,90',
            'lng'     => 'required_without:address|numeric|between:-180,180',
            'address' => 'required_without_all:lat,lng|string|max:500',
            'date'    => 'nullable|date',
            'type'    => 'nullable|in:actual,forecast',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        // 住所からの検索は GeocodingController 経由を推奨するが、
        // 簡易対応として lat/lng 必須とする（フロントエンド側でジオコーディング済み座標を渡す）
        return response()->json(['message' => 'Use /api/wbgt/{station_id} or specify lat/lng via /api/stations/nearest'], 400);
    }

    /**
     * GET /api/wbgt/{station_id}
     * 観測地点IDでWBGTデータを取得
     */
    public function show(string $stationId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'date' => 'nullable|date',
            'type' => 'nullable|in:actual,forecast',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            $data = $this->wbgtService->getLocationWbgt(
                (int) $stationId,
                $request->get('date'),
                $request->get('type', 'forecast')
            );

            return response()->json($data);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json(['message' => '指定された地点が見つかりません'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'WBGTデータの取得に失敗しました', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/wbgt/{station_id}/timeline
     * 時間帯別WBGTデータを取得
     */
    public function timeline(string $stationId, Request $request): JsonResponse
    {
        $date = $request->get('date', now('Asia/Tokyo')->toDateString());

        try {
            $records = WbgtData::where('location_id', $stationId)
                ->where('date', $date)
                ->orderBy('hour')
                ->get(['hour', 'wbgt_value', 'data_type']);

            return response()->json([
                'station_id' => $stationId,
                'date'       => $date,
                'timeline'   => $records,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'タイムラインデータの取得に失敗しました', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/wbgt/bulk
     * 複数地点のWBGTデータを一括取得
     */
    public function bulk(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'location_ids'   => 'required|array|max:20',
            'location_ids.*' => 'integer|min:1',
            'date'           => 'nullable|date',
            'type'           => 'nullable|in:actual,forecast',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $results = [];
        foreach ($request->input('location_ids') as $locationId) {
            try {
                $results[] = $this->wbgtService->getLocationWbgt(
                    $locationId,
                    $request->get('date'),
                    $request->get('type', 'forecast')
                );
            } catch (\Exception) {
                // 個別失敗は無視して続行
            }
        }

        return response()->json(['data' => $results]);
    }
}
