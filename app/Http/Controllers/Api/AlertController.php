<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AlertService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AlertController extends Controller
{
    public function __construct(
        private readonly AlertService $alertService
    ) {}

    /**
     * GET /api/alerts?prefecture={code}
     * 今日有効な熱中症警戒アラートを返す。
     * prefecture を省略した場合は全都道府県分を返す。
     */
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'prefecture' => 'nullable|string|size:2',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        // 最新データを取得（キャッシュ済みなら通信しない）
        $this->alertService->syncFromEnvironmentMinistry();

        $alerts = $this->alertService->getActiveAlerts($request->get('prefecture'));

        return response()->json(['data' => $alerts]);
    }
}
