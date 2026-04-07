<?php

use App\Models\HeatAlert;
use App\Services\AlertService;

// =========================================================
// GET /api/alerts
// =========================================================

describe('GET /api/alerts', function () {
    beforeEach(function () {
        // 環境省への外部通信をモック（キャッシュ済みとして扱う）
        $this->mock(AlertService::class, function ($mock) {
            $mock->shouldReceive('syncFromEnvironmentMinistry')->andReturnNull();
            $mock->shouldReceive('getActiveAlerts')
                ->withNoArgs()
                ->andReturn(HeatAlert::all());
            $mock->shouldReceive('getActiveAlerts')
                ->with(null)
                ->andReturn(HeatAlert::all());
            $mock->shouldReceive('getActiveAlerts')
                ->with('13')
                ->andReturn(HeatAlert::where('prefecture_code', '13')->get());
        });
    });

    it('returns active alerts', function () {
        HeatAlert::factory()->count(3)->create([
            'target_date' => now()->toDateString(),
            'is_active'   => true,
        ]);

        $this->getJson('/api/alerts')
            ->assertOk()
            ->assertJsonStructure(['data']);
    });

    it('filters alerts by prefecture code', function () {
        HeatAlert::factory()->forPrefecture('13')->create(['target_date' => now()->toDateString()]);
        HeatAlert::factory()->forPrefecture('27')->create(['target_date' => now()->toDateString()]);

        $this->getJson('/api/alerts?prefecture=13')
            ->assertOk();
    });

    it('rejects invalid prefecture code format', function () {
        $this->getJson('/api/alerts?prefecture=999')
            ->assertUnprocessable()
            ->assertJsonStructure(['errors' => ['prefecture']]);
    });

    it('returns empty array when no active alerts', function () {
        HeatAlert::factory()->inactive()->create();

        $this->getJson('/api/alerts')
            ->assertOk()
            ->assertJsonFragment(['data' => []]);
    });
});
