<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// 環境省WBGTデータ更新スケジュール（1日3回：5時、14時、17時）
Schedule::command('atsusearch:update-wbgt')->dailyAt('05:00');
Schedule::command('atsusearch:update-wbgt')->dailyAt('14:00');
Schedule::command('atsusearch:update-wbgt')->dailyAt('17:00');
