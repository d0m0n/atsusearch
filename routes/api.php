<?php

use App\Http\Controllers\Api\AlertController;
use App\Http\Controllers\Api\GeocodingController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\WbgtController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// =========================================================
// 認証ユーザー情報
// =========================================================
Route::middleware('auth:sanctum')->get('/user', fn (Request $request) => $request->user());

// =========================================================
// 公開API（未ログインでも利用可能）
// =========================================================

// WBGT データ
Route::prefix('wbgt')->group(function () {
    Route::get('/{station_id}',          [WbgtController::class, 'show']);
    Route::get('/{station_id}/timeline', [WbgtController::class, 'timeline']);
    Route::post('/bulk',                 [WbgtController::class, 'bulk']);
});

// 熱中症警戒アラート
Route::get('/alerts', [AlertController::class, 'index']);

// 逆ジオコーディング・最寄り観測地点
Route::prefix('geocoding')->group(function () {
    Route::post('/reverse', [GeocodingController::class, 'reverse']);
});
Route::get('/stations/nearest', [GeocodingController::class, 'nearestStation']);

// 地点登録（未ログインでも可）
// /api/locations/reverse-geocode との後方互換も兼ねる
Route::prefix('locations')->group(function () {
    Route::post('/',                [LocationController::class, 'store']);
    Route::post('/reverse-geocode', [GeocodingController::class, 'reverse']); // 旧エンドポイント互換
});

// =========================================================
// 認証付きAPI（Laravel Sanctum）
// =========================================================
Route::middleware('auth:sanctum')->prefix('user')->group(function () {
    // お気に入り地点
    Route::get('/locations',            [LocationController::class, 'index']);
    Route::post('/locations',           [LocationController::class, 'store']);
    Route::delete('/locations/{id}',    [LocationController::class, 'destroy']);

    // 検索履歴
    Route::get('/history',              [LocationController::class, 'history']);

    // ユーザー設定（初期表示地域等）
    Route::put('/settings',             [LocationController::class, 'updateSettings']);
});
