<?php

use App\Models\Location;
use App\Models\WbgtData;
use App\Services\WbgtDataService;

// =========================================================
// GET /api/wbgt/{station_id}
// =========================================================

describe('GET /api/wbgt/{station_id}', function () {
    it('returns WBGT data for a valid location', function () {
        $location = Location::factory()->create([
            'name'      => '東京テスト',
            'latitude'  => 35.6814,
            'longitude' => 139.7671,
        ]);

        WbgtData::factory()->create([
            'location_id' => $location->id,
            'date'        => now()->toDateString(),
            'hour'        => 12,
            'wbgt_value'  => 30.5,
            'data_type'   => 'forecast',
        ]);

        $this->getJson("/api/wbgt/{$location->id}")
            ->assertOk()
            ->assertJsonStructure([
                'location' => ['id', 'name', 'latitude', 'longitude'],
                'date',
                'type',
                'wbgt_data',
                'wbgt_station',
            ]);
    });

    it('returns 404 for non-existent location', function () {
        $this->getJson('/api/wbgt/99999')
            ->assertNotFound()
            ->assertJsonFragment(['message' => '指定された地点が見つかりません']);
    });

    it('validates type parameter', function () {
        $location = Location::factory()->create();

        $this->getJson("/api/wbgt/{$location->id}?type=invalid")
            ->assertUnprocessable()
            ->assertJsonStructure(['message', 'errors' => ['type']]);
    });

    it('accepts optional date parameter', function () {
        $location = Location::factory()->create();

        $this->getJson("/api/wbgt/{$location->id}?date=" . now()->toDateString() . '&type=actual')
            ->assertOk();
    });
});

// =========================================================
// GET /api/wbgt/{station_id}/timeline
// =========================================================

describe('GET /api/wbgt/{station_id}/timeline', function () {
    it('returns hourly WBGT timeline', function () {
        $location = Location::factory()->create();

        WbgtData::factory()->count(3)->create([
            'location_id' => $location->id,
            'date'        => now()->toDateString(),
            'data_type'   => 'forecast',
        ]);

        $this->getJson("/api/wbgt/{$location->id}/timeline")
            ->assertOk()
            ->assertJsonStructure([
                'station_id',
                'date',
                'timeline' => [['hour', 'wbgt_value', 'data_type']],
            ]);
    });

    it('returns empty timeline when no data', function () {
        $location = Location::factory()->create();

        $this->getJson("/api/wbgt/{$location->id}/timeline")
            ->assertOk()
            ->assertJsonFragment(['timeline' => []]);
    });
});

// =========================================================
// POST /api/wbgt/bulk
// =========================================================

describe('POST /api/wbgt/bulk', function () {
    it('returns WBGT data for multiple locations', function () {
        $locations = Location::factory()->count(2)->create();

        $this->postJson('/api/wbgt/bulk', [
            'location_ids' => $locations->pluck('id')->toArray(),
            'type'         => 'forecast',
        ])
            ->assertOk()
            ->assertJsonStructure(['data']);
    });

    it('requires location_ids', function () {
        $this->postJson('/api/wbgt/bulk', [])
            ->assertUnprocessable()
            ->assertJsonStructure(['errors' => ['location_ids']]);
    });

    it('rejects more than 20 locations', function () {
        $this->postJson('/api/wbgt/bulk', [
            'location_ids' => range(1, 21),
        ])
            ->assertUnprocessable();
    });
});
