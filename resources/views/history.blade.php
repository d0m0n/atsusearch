@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">📍 地点履歴管理</h1>
                <p class="text-gray-600 mt-2">保存した地点のWBGT履歴を確認・管理できます</p>
            </div>
            <a href="{{ route('search') }}" 
               class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
                🔍 新しい地点を検索
            </a>
        </div>

        <div id="history-app">
            <user-location-history></user-location-history>
        </div>
    </div>
</div>

@vite(['resources/js/history.js'])
@endsection