<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Services\WbgtDataService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class LocationController extends Controller
{
    public function __construct(
        private WbgtDataService $wbgtService
    ) {}

    /**
     * ユーザーの地点一覧を取得
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated'
            ], 401);
        }

        $locations = Location::where('user_id', $user->id)
            ->with('wbgtData')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['locations' => $locations]);
    }

    /**
     * 新しい地点を登録
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'is_favorite' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();
            $userId = $user ? $user->id : null;

            $location = $this->wbgtService->createLocationFromCoordinates(
                $request->get('latitude'),
                $request->get('longitude'),
                $userId
            );

            // ユーザーが提供した情報で更新
            $location->update([
                'name' => $request->get('name', $location->name),
                'address' => $request->get('address', $location->address),
                'is_favorite' => $request->get('is_favorite', false)
            ]);

            return response()->json([
                'location' => $location,
                'message' => 'Location created successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create location',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 地点情報を取得
     */
    public function show(string $id): JsonResponse
    {
        try {
            $location = Location::with('wbgtData')->findOrFail($id);
            
            return response()->json(['location' => $location]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Location not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * 地点情報を更新
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'is_favorite' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $location = Location::findOrFail($id);
            
            // ユーザーの所有する地点のみ更新可能
            $user = $request->user();
            if ($user && $location->user_id !== $user->id) {
                return response()->json([
                    'message' => 'Unauthorized'
                ], 403);
            }

            $location->update($request->only(['name', 'address', 'is_favorite']));

            return response()->json([
                'location' => $location,
                'message' => 'Location updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update location',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 地点を削除
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        try {
            $location = Location::findOrFail($id);
            
            // ユーザーの所有する地点のみ削除可能
            $user = $request->user();
            if ($user && $location->user_id !== $user->id) {
                return response()->json([
                    'message' => 'Unauthorized'
                ], 403);
            }

            $location->delete();

            return response()->json([
                'message' => 'Location deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete location',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 逆ジオコーディング（座標から住所取得）
     */
    public function reverseGeocode(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // モックデータを返す（実際にはGoogle Maps APIを使用）
            $latitude = $request->get('latitude');
            $longitude = $request->get('longitude');
            
            return response()->json([
                'name' => "緯度{$latitude}, 経度{$longitude}",
                'address' => "緯度: {$latitude}, 経度: {$longitude}",
                'latitude' => $latitude,
                'longitude' => $longitude
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Reverse geocoding failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
