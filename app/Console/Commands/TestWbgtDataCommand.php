<?php

namespace App\Console\Commands;

use App\Services\WbgtDataService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestWbgtDataCommand extends Command
{
    protected $signature = 'atsusearch:test-wbgt {--force : Force fetch even if recently updated}';
    protected $description = 'Test Environment Ministry WBGT data integration';

    public function handle(WbgtDataService $wbgtService): int
    {
        $this->info('🌡️ Testing Environment Ministry WBGT Data Integration...');
        
        // テスト1: CSV URLアクセステスト
        $this->info('📊 Testing CSV URL access...');
        $testUrls = [
            'https://www.wbgt.env.go.jp/mntr/2025/wbgt_2025/wbgt_47412_202507.csv', // 札幌
            'https://www.wbgt.env.go.jp/mntr/dl/Utsunomiya_202507.csv', // 宇都宮
        ];
        
        foreach ($testUrls as $url) {
            $this->info("Testing: {$url}");
            try {
                $response = Http::timeout(10)->get($url);
                if ($response->successful()) {
                    $this->info("✅ Success: {$response->status()}");
                    $content = $response->body();
                    $lines = explode("\n", $content);
                    $this->info("📄 Content lines: " . count($lines));
                    if (count($lines) > 0) {
                        $this->info("📑 First line: " . substr($lines[0], 0, 100));
                    }
                } else {
                    $this->error("❌ Failed: {$response->status()}");
                }
            } catch (\Exception $e) {
                $this->error("❌ Error: " . $e->getMessage());
            }
        }
        
        // テスト2: WBGTサービスの実行
        $this->info('🔄 Testing WBGT Service...');
        try {
            if ($this->option('force')) {
                $this->info('🚀 Forcing cache clear...');
                \Illuminate\Support\Facades\Cache::forget('wbgt_last_update');
            }
            
            $wbgtService->fetchAndStoreWbgtData();
            $this->info('✅ WBGT Service executed successfully');
        } catch (\Exception $e) {
            $this->error('❌ WBGT Service failed: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
        }
        
        // テスト3: データベース確認
        $this->info('🗄️ Checking database...');
        $locationCount = \App\Models\Location::count();
        $wbgtDataCount = \App\Models\WbgtData::count();
        
        $this->info("📊 Locations in DB: {$locationCount}");
        $this->info("📈 WBGT Data records: {$wbgtDataCount}");
        
        if ($wbgtDataCount > 0) {
            $latest = \App\Models\WbgtData::latest()->first();
            $this->info("🕒 Latest WBGT data: " . $latest->date . ' ' . $latest->hour . ':00 - ' . $latest->wbgt_value . '°C');
        }
        
        $this->info('🎉 Test completed!');
        return Command::SUCCESS;
    }
}
