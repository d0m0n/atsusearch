<?php

use App\Http\Controllers\LocationController;
use App\Http\Controllers\WbgtController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// WBGT関連API（未ログインでもアクセス可能）
Route::prefix('wbgt')->group(function () {
    Route::get('/{locationId}', [WbgtController::class, 'show']);
    Route::post('/bulk', [WbgtController::class, 'bulk']);
    Route::get('/nearby', [WbgtController::class, 'nearby']);
    Route::post('/search', [WbgtController::class, 'search']); // 新しいエンドポイント
    Route::post('/update', [WbgtController::class, 'update']);
});

// 地点関連API
Route::prefix('locations')->group(function () {
    // 未ログインでもアクセス可能
    Route::post('/', [LocationController::class, 'store']);
    Route::get('/{id}', [LocationController::class, 'show']);
    Route::post('/reverse-geocode', [LocationController::class, 'reverseGeocode']);
    Route::post('/temperature', [LocationController::class, 'getTemperature']);
    
    // ログイン必須
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/', [LocationController::class, 'index']);
        Route::put('/{id}', [LocationController::class, 'update']);
        Route::delete('/{id}', [LocationController::class, 'destroy']);
    });
});