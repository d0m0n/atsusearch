<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MapController extends Controller
{
    /**
     * AtsuSearch map page
     */
    public function index()
    {
        $googleMapsApiKey = config('services.google_maps.api_key');
        
        // APIキーが設定されていない場合のエラーハンドリング
        if (!$googleMapsApiKey) {
            throw new \Exception('Google Maps API key is not configured. Please set GOOGLE_MAPS_API_KEY in your .env file.');
        }
        
        return view('map.index', [
            'googleMapsApiKey' => $googleMapsApiKey,
            'isLoggedIn' => auth()->check()
        ]);
    }
}