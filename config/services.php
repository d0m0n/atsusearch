<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key'    => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel'              => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // =====================================================================
    // Google Maps API
    // GeocodingController: config('services.google_maps.key')
    // =====================================================================
    'google_maps' => [
        'key'     => env('GOOGLE_MAPS_API_KEY'),
        'api_key' => env('GOOGLE_MAPS_API_KEY'), // 後方互換
    ],

    // =====================================================================
    // 環境省 WBGT
    // WbgtDataService : config('services.wbgt.base_url')
    //                   config('services.wbgt.forecast_url')
    // AlertService    : config('services.wbgt.alert_url')
    //
    // .env に設定する変数:
    //   WBGT_BASE_URL     = https://www.wbgt.env.go.jp/prev15WG/dl/
    //   WBGT_FORECAST_URL = https://www.wbgt.env.go.jp/prev15WG/dl/
    //   WBGT_ALERT_URL    = https://www.wbgt.env.go.jp/alert_data/
    //   WBGT_CACHE_TTL    = 3600
    // =====================================================================
    'wbgt' => [
        'base_url'     => env('WBGT_BASE_URL',     'https://www.wbgt.env.go.jp/prev15WG/dl/'),
        'forecast_url' => env('WBGT_FORECAST_URL', 'https://www.wbgt.env.go.jp/prev15WG/dl/'),
        'alert_url'    => env('WBGT_ALERT_URL',    'https://www.wbgt.env.go.jp/alert_data/'),
        'cache_ttl'    => (int) env('WBGT_CACHE_TTL', 3600),
    ],

];
