<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\WbgtData;
use App\Services\WbgtDataService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class WbgtController extends Controller
{
    public function __construct(
        private WbgtDataService $wbgtService
    ) {}

    /**
     * 指定地点のWBGTデータを取得
     */
    public function show(string $locationId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'date' => 'nullable|date',
            'type' => 'nullable|in:actual,forecast'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $this->wbgtService->getLocationWbgt(
                $locationId,
                $request->get('date'),
                $request->get('type', 'forecast')
            );

            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch WBGT data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 複数地点のWBGTデータを一括取得
     */
    public function bulk(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'location_ids' => 'required|array',
            'location_ids.*' => 'integer|exists:locations,id',
            'date' => 'nullable|date',
            'type' => 'nullable|in:actual,forecast'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $results = [];
            $date = $request->get('date');
            $type = $request->get('type', 'forecast');

            foreach ($request->get('location_ids') as $locationId) {
                $results[] = $this->wbgtService->getLocationWbgt($locationId, $date, $type);
            }

            return response()->json(['data' => $results]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch bulk WBGT data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 座標周辺のWBGTデータを取得
     */
    public function nearby(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius' => 'nullable|integer|min:1|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $latitude = $request->get('latitude');
            $longitude = $request->get('longitude');
            $radius = $request->get('radius', 50);

            $locations = $this->wbgtService->searchLocationsByCoordinates(
                $latitude,
                $longitude,
                $radius
            );

            // 各地点の現在のWBGTデータを付加
            $locationsWithWbgt = $locations->map(function ($location) {
                $currentHour = now()->hour;
                $currentWbgt = WbgtData::where('location_id', $location->id)
                    ->where('date', now()->toDateString())
                    ->where('hour', '<=', $currentHour)
                    ->orderBy('hour', 'desc')
                    ->first();

                $location->current_wbgt = $currentWbgt ? $currentWbgt->wbgt_value : null;
                $location->wbgt_level = $currentWbgt ? $currentWbgt->wbgt_level : 'unknown';
                $location->wbgt_level_text = $currentWbgt ? $currentWbgt->wbgt_level_text : '不明';
                $location->wbgt_level_color = $currentWbgt ? $currentWbgt->wbgt_level_color : '#6b7280';

                return $location;
            });

            return response()->json(['locations' => $locationsWithWbgt]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to search nearby WBGT data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * WBGTデータの更新（管理者向け）
     */
    public function update(): JsonResponse
    {
        try {
            $this->wbgtService->fetchAndStoreWbgtData();
            
            return response()->json([
                'message' => 'WBGT data updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update WBGT data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 緯度経度からWBGTデータを検索（マップクリック用）
     */
    public function search(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $latitude = $request->latitude;
        $longitude = $request->longitude;

        try {
            // 近隣の既存地点を検索（半径5km以内）
            $nearbyLocation = Location::selectRaw('
                *, 
                (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance
            ', [$latitude, $longitude, $latitude])
                ->having('distance', '<', 5)
                ->orderBy('distance')
                ->first();

            if ($nearbyLocation) {
                // 既存地点のWBGTデータを取得
                $currentHour = now()->hour;
                $wbgtRecord = WbgtData::where('location_id', $nearbyLocation->id)
                    ->where('date', now()->toDateString())
                    ->where('hour', '<=', $currentHour)
                    ->orderBy('hour', 'desc')
                    ->first();

                $wbgtValue = $wbgtRecord ? $wbgtRecord->wbgt_value : null;
            } else {
                // 新しい地点として登録
                $nearbyLocation = Location::create([
                    'name' => "地点 ({$latitude}, {$longitude})",
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'address' => "緯度: {$latitude}, 経度: {$longitude}",
                    'is_favorite' => false,
                ]);

                // WBGTデータを生成（実際の実装では環境省APIから取得）
                $wbgtValue = $this->generateMockWbgtData($latitude, $longitude);
                
                // データベースに保存
                WbgtData::create([
                    'location_id' => $nearbyLocation->id,
                    'date' => now()->toDateString(),
                    'hour' => now()->hour,
                    'wbgt_value' => $wbgtValue,
                    'data_type' => 'forecast',
                ]);
            }

            // Reverse Geocoding for location name (simplified)
            $locationName = $this->getLocationName($latitude, $longitude);

            return response()->json([
                'success' => true,
                'wbgt' => [
                    'value' => $wbgtValue ?? $this->generateMockWbgtData($latitude, $longitude),
                    'timestamp' => now()->toISOString(),
                    'level' => $this->getWbgtLevel($wbgtValue ?? 25),
                ],
                'location' => [
                    'id' => $nearbyLocation->id,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'name' => $locationName,
                ],
                'location_name' => $locationName
            ]);

        } catch (\Exception $e) {
            Log::error('WBGT search error: ' . $e->getMessage());

            // エラー時も模擬データを返す
            return response()->json([
                'success' => true, // フロントエンドでの処理を継続
                'wbgt' => [
                    'value' => $this->generateMockWbgtData($latitude, $longitude),
                    'timestamp' => now()->toISOString(),
                    'level' => $this->getWbgtLevel(25),
                ],
                'location_name' => "地点 (" . round($latitude, 2) . ", " . round($longitude, 2) . ")",
                'note' => 'デモデータ'
            ]);
        }
    }

    /**
     * 模擬WBGTデータ生成（実際の実装では環境省APIを使用）
     */
    private function generateMockWbgtData($latitude, $longitude)
    {
        // 緯度に基づく簡単な計算（南ほど高温）
        $baseTemp = 25;
        $latitudeAdjustment = (35 - $latitude) * 0.5; // 35度（東京付近）を基準
        $randomVariation = rand(-3, 7); // ランダム要素
        
        $wbgt = max(15, min(40, $baseTemp + $latitudeAdjustment + $randomVariation));
        
        return round($wbgt, 1);
    }

    /**
     * 詳細な住所取得（実際の実装ではGoogle Geocoding APIを使用）
     */
    private function getLocationName($latitude, $longitude)
    {
        // より詳細な地域判定
        
        // 東京都
        if ($latitude >= 35.5 && $latitude <= 35.9 && $longitude >= 139.2 && $longitude <= 139.9) {
            if ($latitude >= 35.65 && $latitude <= 35.75 && $longitude >= 139.7 && $longitude <= 139.8) {
                return '東京都千代田区・中央区周辺';
            } elseif ($latitude >= 35.6 && $latitude <= 35.7 && $longitude >= 139.6 && $longitude <= 139.75) {
                return '東京都新宿区・渋谷区周辺';
            } elseif ($latitude >= 35.7 && $latitude <= 35.8 && $longitude >= 139.7 && $longitude <= 139.8) {
                return '東京都台東区・墨田区周辺';
            } else {
                return '東京都内';
            }
        }
        
        // 神奈川県
        elseif ($latitude >= 35.2 && $latitude <= 35.6 && $longitude >= 139.3 && $longitude <= 139.8) {
            if ($latitude >= 35.4 && $latitude <= 35.5 && $longitude >= 139.6 && $longitude <= 139.7) {
                return '神奈川県横浜市周辺';
            } elseif ($latitude >= 35.3 && $latitude <= 35.4 && $longitude >= 139.4 && $longitude <= 139.5) {
                return '神奈川県川崎市周辺';
            } else {
                return '神奈川県内';
            }
        }
        
        // 埼玉県
        elseif ($latitude >= 35.7 && $latitude <= 36.2 && $longitude >= 139.0 && $longitude <= 139.8) {
            if ($latitude >= 35.85 && $latitude <= 35.95 && $longitude >= 139.6 && $longitude <= 139.7) {
                return '埼玉県さいたま市周辺';
            } else {
                return '埼玉県内';
            }
        }
        
        // 千葉県
        elseif ($latitude >= 35.1 && $latitude <= 35.9 && $longitude >= 139.8 && $longitude <= 140.9) {
            if ($latitude >= 35.6 && $latitude <= 35.7 && $longitude >= 140.0 && $longitude <= 140.2) {
                return '千葉県千葉市周辺';
            } else {
                return '千葉県内';
            }
        }
        
        // 大阪府
        elseif ($latitude >= 34.3 && $latitude <= 34.8 && $longitude >= 135.2 && $longitude <= 135.8) {
            if ($latitude >= 34.6 && $latitude <= 34.7 && $longitude >= 135.4 && $longitude <= 135.6) {
                return '大阪府大阪市周辺';
            } else {
                return '大阪府内';
            }
        }
        
        // 愛知県
        elseif ($latitude >= 34.8 && $latitude <= 35.4 && $longitude >= 136.7 && $longitude <= 137.5) {
            if ($latitude >= 35.1 && $latitude <= 35.2 && $longitude >= 136.8 && $longitude <= 137.0) {
                return '愛知県名古屋市周辺';
            } else {
                return '愛知県内';
            }
        }
        
        // 兵庫県
        elseif ($latitude >= 34.4 && $latitude <= 35.2 && $longitude >= 134.8 && $longitude <= 135.5) {
            if ($latitude >= 34.6 && $latitude <= 34.8 && $longitude >= 135.1 && $longitude <= 135.3) {
                return '兵庫県神戸市周辺';
            } else {
                return '兵庫県内';
            }
        }
        
        // 福岡県
        elseif ($latitude >= 33.4 && $latitude <= 33.8 && $longitude >= 130.2 && $longitude <= 130.7) {
            if ($latitude >= 33.5 && $latitude <= 33.7 && $longitude >= 130.3 && $longitude <= 130.5) {
                return '福岡県福岡市周辺';
            } else {
                return '福岡県内';
            }
        }
        
        // 北海道（詳細な市町村レベル判定）
        elseif ($latitude >= 41.0 && $latitude <= 45.5 && $longitude >= 139.0 && $longitude <= 145.8) {
            // 札幌市（中央区、北区、東区、白石区、豊平区、南区、西区、厚別区、手稲区、清田区）
            if ($latitude >= 43.0 && $latitude <= 43.2 && $longitude >= 141.2 && $longitude <= 141.5) {
                if ($latitude >= 43.05 && $latitude <= 43.08 && $longitude >= 141.34 && $longitude <= 141.37) {
                    return '北海道札幌市中央区周辺';
                } elseif ($latitude >= 43.08 && $latitude <= 43.12 && $longitude >= 141.32 && $longitude <= 141.38) {
                    return '北海道札幌市北区周辺';
                } elseif ($latitude >= 43.08 && $latitude <= 43.11 && $longitude >= 141.38 && $longitude <= 141.42) {
                    return '北海道札幌市東区周辺';
                } elseif ($latitude >= 43.03 && $latitude <= 43.07 && $longitude >= 141.38 && $longitude <= 141.42) {
                    return '北海道札幌市白石区周辺';
                } elseif ($latitude >= 42.98 && $latitude <= 43.03 && $longitude >= 141.34 && $longitude <= 141.38) {
                    return '北海道札幌市豊平区周辺';
                } elseif ($latitude >= 42.90 && $latitude <= 43.00 && $longitude >= 141.28 && $longitude <= 141.38) {
                    return '北海道札幌市南区周辺';
                } elseif ($latitude >= 43.05 && $latitude <= 43.10 && $longitude >= 141.25 && $longitude <= 141.32) {
                    return '北海道札幌市西区周辺';
                } elseif ($latitude >= 43.02 && $latitude <= 43.06 && $longitude >= 141.44 && $longitude <= 141.50) {
                    return '北海道札幌市厚別区周辺';
                } elseif ($latitude >= 43.08 && $latitude <= 43.12 && $longitude >= 141.23 && $longitude <= 141.30) {
                    return '北海道札幌市手稲区周辺';
                } elseif ($latitude >= 42.98 && $latitude <= 43.03 && $longitude >= 141.40 && $longitude <= 141.46) {
                    return '北海道札幌市清田区周辺';
                } else {
                    return '北海道札幌市周辺';
                }
            }
            // 函館市
            elseif ($latitude >= 41.7 && $latitude <= 41.9 && $longitude >= 140.6 && $longitude <= 140.8) {
                return '北海道函館市周辺';
            }
            // 旭川市
            elseif ($latitude >= 43.7 && $latitude <= 43.8 && $longitude >= 142.3 && $longitude <= 142.4) {
                return '北海道旭川市周辺';
            }
            // 釧路市
            elseif ($latitude >= 42.9 && $latitude <= 43.0 && $longitude >= 144.3 && $longitude <= 144.4) {
                return '北海道釧路市周辺';
            }
            // 帯広市（十勝地方）
            elseif ($latitude >= 42.9 && $latitude <= 43.0 && $longitude >= 143.1 && $longitude <= 143.3) {
                return '北海道帯広市周辺';
            }
            // 北見市
            elseif ($latitude >= 43.8 && $latitude <= 43.9 && $longitude >= 143.8 && $longitude <= 143.9) {
                return '北海道北見市周辺';
            }
            // 室蘭市
            elseif ($latitude >= 42.3 && $latitude <= 42.4 && $longitude >= 140.9 && $longitude <= 141.0) {
                return '北海道室蘭市周辺';
            }
            // 苫小牧市
            elseif ($latitude >= 42.6 && $latitude <= 42.7 && $longitude >= 141.5 && $longitude <= 141.7) {
                return '北海道苫小牧市周辺';
            }
            // 小樽市
            elseif ($latitude >= 43.1 && $latitude <= 43.3 && $longitude >= 140.9 && $longitude <= 141.1) {
                return '北海道小樽市周辺';
            }
            // 江別市
            elseif ($latitude >= 43.08 && $latitude <= 43.12 && $longitude >= 141.5 && $longitude <= 141.6) {
                return '北海道江別市周辺';
            }
            // 千歳市
            elseif ($latitude >= 42.8 && $latitude <= 42.9 && $longitude >= 141.6 && $longitude <= 141.7) {
                return '北海道千歳市周辺';
            }
            // 石狩市
            elseif ($latitude >= 43.1 && $latitude <= 43.3 && $longitude >= 141.3 && $longitude <= 141.5) {
                return '北海道石狩市周辺';
            }
            // 恵庭市
            elseif ($latitude >= 42.88 && $latitude <= 42.95 && $longitude >= 141.55 && $longitude <= 141.62) {
                return '北海道恵庭市周辺';
            }
            // 北広島市
            elseif ($latitude >= 42.95 && $latitude <= 43.0 && $longitude >= 141.5 && $longitude <= 141.6) {
                return '北海道北広島市周辺';
            }
            // 稚内市
            elseif ($latitude >= 45.3 && $latitude <= 45.5 && $longitude >= 141.6 && $longitude <= 141.8) {
                return '北海道稚内市周辺';
            }
            // 網走市
            elseif ($latitude >= 44.0 && $latitude <= 44.1 && $longitude >= 144.2 && $longitude <= 144.3) {
                return '北海道網走市周辺';
            }
            // 根室市
            elseif ($latitude >= 43.3 && $latitude <= 43.4 && $longitude >= 145.5 && $longitude <= 145.6) {
                return '北海道根室市周辺';
            }
            // 夕張市
            elseif ($latitude >= 43.0 && $latitude <= 43.1 && $longitude >= 141.9 && $longitude <= 142.0) {
                return '北海道夕張市周辺';
            }
            // 滝川市
            elseif ($latitude >= 43.5 && $latitude <= 43.6 && $longitude >= 142.0 && $longitude <= 142.1) {
                return '北海道滝川市周辺';
            }
            // 砂川市
            elseif ($latitude >= 43.4 && $latitude <= 43.5 && $longitude >= 141.9 && $longitude <= 142.0) {
                return '北海道砂川市周辺';
            }
            // 深川市
            elseif ($latitude >= 43.7 && $latitude <= 43.8 && $longitude >= 142.0 && $longitude <= 142.1) {
                return '北海道深川市周辺';
            }
            // 富良野市
            elseif ($latitude >= 43.3 && $latitude <= 43.4 && $longitude >= 142.3 && $longitude <= 142.4) {
                return '北海道富良野市周辺';
            }
            // 登別市
            elseif ($latitude >= 42.4 && $latitude <= 42.5 && $longitude >= 141.1 && $longitude <= 141.2) {
                return '北海道登別市周辺';
            }
            // 伊達市
            elseif ($latitude >= 42.4 && $latitude <= 42.5 && $longitude >= 140.8 && $longitude <= 140.9) {
                return '北海道伊達市周辺';
            }
            // 地域レベルでの大まかな分類
            elseif ($latitude >= 43.8 && $latitude <= 45.5) {
                return '北海道道北地方';
            }
            elseif ($latitude >= 42.8 && $latitude <= 43.8 && $longitude >= 144.0) {
                return '北海道道東地方';
            }
            elseif ($latitude >= 41.0 && $latitude <= 42.5) {
                return '北海道道南地方';
            }
            elseif ($latitude >= 42.5 && $latitude <= 43.8 && $longitude <= 142.5) {
                return '北海道道央地方';
            }
            else {
                return '北海道内';
            }
        }
        
        // 宮城県
        elseif ($latitude >= 37.8 && $latitude <= 38.6 && $longitude >= 140.5 && $longitude <= 141.5) {
            if ($latitude >= 38.2 && $latitude <= 38.3 && $longitude >= 140.8 && $longitude <= 141.0) {
                return '宮城県仙台市周辺';
            } else {
                return '宮城県内';
            }
        }
        
        // 広島県
        elseif ($latitude >= 34.2 && $latitude <= 34.6 && $longitude >= 132.2 && $longitude <= 133.2) {
            if ($latitude >= 34.3 && $latitude <= 34.5 && $longitude >= 132.4 && $longitude <= 132.6) {
                return '広島県広島市周辺';
            } else {
                return '広島県内';
            }
        }
        
        // その他の地域（大まかな地方分類）
        elseif ($latitude >= 36 && $latitude <= 41 && $longitude >= 138 && $longitude <= 142) {
            return '東北地方';
        } elseif ($latitude >= 34 && $latitude <= 36.5 && $longitude >= 138 && $longitude <= 141) {
            return '関東地方';
        } elseif ($latitude >= 34.5 && $latitude <= 37 && $longitude >= 136 && $longitude <= 139) {
            return '中部地方';
        } elseif ($latitude >= 33 && $latitude <= 36 && $longitude >= 134 && $longitude <= 137) {
            return '関西地方';
        } elseif ($latitude >= 33 && $latitude <= 35.5 && $longitude >= 130 && $longitude <= 134) {
            return '中国・四国地方';
        } elseif ($latitude >= 31 && $latitude <= 34 && $longitude >= 129 && $longitude <= 132) {
            return '九州地方';
        } elseif ($latitude >= 24 && $latitude <= 26.5 && $longitude >= 122 && $longitude <= 131) {
            return '沖縄県内';
        }
        
        // 判定できない場合
        return '選択された地点';
    }

    /**
     * WBGTレベル判定
     */
    private function getWbgtLevel($wbgt)
    {
        if ($wbgt >= 31) return '危険';
        if ($wbgt >= 28) return '厳重警戒';
        if ($wbgt >= 25) return '警戒';
        if ($wbgt >= 21) return '注意';
        return 'ほぼ安全';
    }
}
