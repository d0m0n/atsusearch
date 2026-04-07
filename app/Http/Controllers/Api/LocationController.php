<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\SearchHistory;
use App\Services\NearestStationService;
use App\Services\WbgtDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LocationController extends Controller
{
    public function __construct(
        private readonly WbgtDataService      $wbgtService,
        private readonly NearestStationService $nearestStationService
    ) {}

    /**
     * GET /api/user/locations
     * ログインユーザーのお気に入り地点一覧
     */
    public function index(Request $request): JsonResponse
    {
        $locations = Location::where('user_id', $request->user()->id)
            ->where('is_favorite', true)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['data' => $locations]);
    }

    /**
     * POST /api/user/locations
     * お気に入り地点を登録
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name'        => 'nullable|string|max:255',
            'address'     => 'nullable|string',
            'latitude'    => 'required|numeric|between:-90,90',
            'longitude'   => 'required|numeric|between:-180,180',
            'is_favorite' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            $user   = $request->user();
            $userId = $user?->id;

            $location = $this->wbgtService->createLocationFromCoordinates(
                $request->float('latitude'),
                $request->float('longitude'),
                $userId
            );

            $location->update([
                'name'        => $request->get('name', $location->name),
                'address'     => $request->get('address', $location->address),
                'is_favorite' => $request->boolean('is_favorite', false),
            ]);

            // 検索履歴を記録（ログイン時のみ）
            if ($userId) {
                $station = $this->nearestStationService->findNearest(
                    $request->float('latitude'),
                    $request->float('longitude')
                );

                SearchHistory::create([
                    'user_id'     => $userId,
                    'query'       => $request->get('address'),
                    'latitude'    => $request->float('latitude'),
                    'longitude'   => $request->float('longitude'),
                    'station_id'  => null, // wbgt_stationsテーブル整備後に紐付け
                    'searched_at' => now(),
                ]);
            }

            return response()->json(['location' => $location, 'message' => '地点を登録しました'], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => '地点の登録に失敗しました', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * DELETE /api/user/locations/{id}
     * お気に入り地点を削除
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $location = Location::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $location->delete();

        return response()->json(['message' => '地点を削除しました']);
    }

    /**
     * GET /api/user/history
     * 検索履歴を取得
     */
    public function history(Request $request): JsonResponse
    {
        $history = SearchHistory::where('user_id', $request->user()->id)
            ->with('station:id,station_code,name')
            ->orderBy('searched_at', 'desc')
            ->limit(50)
            ->get();

        return response()->json(['data' => $history]);
    }

    /**
     * PUT /api/user/settings
     * 初期表示地域等のユーザー設定を更新
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'default_latitude'  => 'nullable|numeric|between:-90,90',
            'default_longitude' => 'nullable|numeric|between:-180,180',
            'default_address'   => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $request->user()->update($request->only([
            'default_latitude',
            'default_longitude',
            'default_address',
        ]));

        return response()->json(['message' => '設定を更新しました']);
    }
}
