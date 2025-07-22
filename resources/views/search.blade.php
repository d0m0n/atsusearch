@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gradient-to-b from-blue-50 to-white">
    <div class="container mx-auto px-4 py-8">
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-gray-800 mb-4">🌡️ アツサーチ</h1>
            <p class="text-xl text-gray-600 mb-2">暑さを検索、安全を発見</p>
            <p class="text-sm text-gray-500">
                地図上をクリックして、その地点の暑さ指数(WBGT)をリアルタイムで確認できます
            </p>
        </div>

        @guest
        <div class="bg-blue-100 border border-blue-300 text-blue-800 px-4 py-3 rounded mb-6">
            <p class="text-sm">
                <strong>未ログインでも利用可能！</strong> 
                <a href="{{ route('register') }}" class="underline hover:text-blue-900">アカウント作成</a>
                または
                <a href="{{ route('login') }}" class="underline hover:text-blue-900">ログイン</a>
                すると、お気に入り地点の保存や履歴管理が利用できます。
            </p>
        </div>
        @endguest

        @auth
        <div class="bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded mb-6">
            <p class="text-sm">
                <strong>{{ auth()->user()->name }}さん、こんにちは！</strong> 
                <a href="{{ route('history') }}" class="underline hover:text-green-900">履歴管理</a>
                でお気に入り地点を確認できます。
            </p>
        </div>
        @endauth

        <!-- Vue.js コンポーネント -->
        <div id="atsu-search-app">
            <!-- テストコンポーネント -->
            <test-component 
                api-key="{{ config('services.google_maps.api_key', 'demo-key') }}"
                :is-logged-in="{{ auth()->check() ? 'true' : 'false' }}"
            ></test-component>
            
            <!-- メインコンポーネント -->
            <atsu-search-map 
                api-key="{{ config('services.google_maps.api_key', 'demo-key') }}"
                :is-logged-in="{{ auth()->check() ? 'true' : 'false' }}"
            ></atsu-search-map>
        </div>

        <!-- WBGT レベル説明 -->
        <div class="bg-white rounded-lg shadow-md p-6 mt-8">
            <h3 class="text-lg font-bold text-gray-800 mb-4">暑さ指数(WBGT)について</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                <div class="text-center">
                    <div class="w-full h-12 bg-green-500 rounded-t flex items-center justify-center text-white font-bold">
                        ～21°C
                    </div>
                    <div class="bg-gray-100 p-3 rounded-b">
                        <p class="text-xs font-medium">ほぼ安全</p>
                        <p class="text-xs text-gray-600">適宜水分補給</p>
                    </div>
                </div>
                <div class="text-center">
                    <div class="w-full h-12 bg-blue-500 rounded-t flex items-center justify-center text-white font-bold">
                        21-25°C
                    </div>
                    <div class="bg-gray-100 p-3 rounded-b">
                        <p class="text-xs font-medium">注意</p>
                        <p class="text-xs text-gray-600">水分補給を忘れずに</p>
                    </div>
                </div>
                <div class="text-center">
                    <div class="w-full h-12 bg-yellow-500 rounded-t flex items-center justify-center text-white font-bold">
                        25-28°C
                    </div>
                    <div class="bg-gray-100 p-3 rounded-b">
                        <p class="text-xs font-medium">警戒</p>
                        <p class="text-xs text-gray-600">積極的に休憩</p>
                    </div>
                </div>
                <div class="text-center">
                    <div class="w-full h-12 bg-orange-500 rounded-t flex items-center justify-center text-white font-bold">
                        28-31°C
                    </div>
                    <div class="bg-gray-100 p-3 rounded-b">
                        <p class="text-xs font-medium">厳重警戒</p>
                        <p class="text-xs text-gray-600">激しい運動は中止</p>
                    </div>
                </div>
                <div class="text-center">
                    <div class="w-full h-12 bg-red-600 rounded-t flex items-center justify-center text-white font-bold">
                        31°C～
                    </div>
                    <div class="bg-gray-100 p-3 rounded-b">
                        <p class="text-xs font-medium">危険</p>
                        <p class="text-xs text-gray-600">運動は原則中止</p>
                    </div>
                </div>
            </div>
            <p class="text-xs text-gray-600 mt-4">
                ※ 暑さ指数(WBGT)は環境省の「熱中症予防情報サイト」のデータを活用しています
            </p>
        </div>

        <!-- お知らせ -->
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mt-6">
            <h4 class="font-bold text-gray-800 mb-2">🚨 ご注意</h4>
            <ul class="text-sm text-gray-700 space-y-1">
                <li>• この情報は参考値です。実際の環境によってリスクは変動します</li>
                <li>• 体調や年齢、持病などを考慮して適切な熱中症対策を行ってください</li>
                <li>• 緊急時は躊躇せず医療機関に相談してください</li>
            </ul>
        </div>
    </div>
</div>

@vite(['resources/js/search.js'])
@endsection