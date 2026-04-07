<?php

namespace App\Console\Commands;

use App\Services\AlertService;
use App\Services\WbgtDataService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * 環境省WBGTデータ更新コマンド。
 *
 * 実行タイミング: 毎日 5時 / 14時 / 17時（環境省の更新スケジュールに合わせる）
 *
 * @see https://www.wbgt.env.go.jp/
 */
class UpdateWbgtData extends Command
{
    protected $signature = 'atsusearch:update-wbgt
                            {--force : キャッシュを無視して強制更新する}';

    protected $description = '環境省からWBGT予測値・実況値を取得してデータベースを更新する';

    public function handle(WbgtDataService $wbgtService, AlertService $alertService): int
    {
        $this->info('AtsuSearch — WBGTデータ更新を開始します');

        if ($this->option('force')) {
            $this->info('  [force] キャッシュをクリアします');
            Cache::forget('wbgt_last_update');
            Cache::forget('heat_alert_' . now('Asia/Tokyo')->toDateString());
        }

        // --- WBGTデータ更新 ---
        $this->line('  WBGTデータを取得中...');
        try {
            $wbgtService->fetchAndStoreWbgtData();
            $this->info('  ✓ WBGTデータを更新しました');
        } catch (\Exception $e) {
            $this->error('  ✗ WBGTデータの更新に失敗: ' . $e->getMessage());
            // WBGTが失敗してもアラートは続行する
        }

        // --- 熱中症警戒アラート更新 ---
        $this->line('  熱中症警戒アラートを取得中...');
        try {
            $alertService->syncFromEnvironmentMinistry();
            $this->info('  ✓ アラートデータを更新しました');
        } catch (\Exception $e) {
            $this->error('  ✗ アラートデータの更新に失敗: ' . $e->getMessage());
        }

        // --- 結果サマリー ---
        $wbgtCount  = \App\Models\WbgtData::where('date', now('Asia/Tokyo')->toDateString())->count();
        $alertCount = \App\Models\HeatAlert::where('is_active', true)
            ->where('target_date', now('Asia/Tokyo')->toDateString())
            ->count();

        $this->line('');
        $this->line('  本日のWBGTレコード数: ' . $wbgtCount);
        $this->line('  有効アラート数:       ' . $alertCount);
        $this->info('更新完了');

        return Command::SUCCESS;
    }
}
