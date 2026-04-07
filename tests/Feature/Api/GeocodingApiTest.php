<?php

use Illuminate\Support\Facades\Http;

// =========================================================
// POST /api/geocoding/reverse
// =========================================================

describe('POST /api/geocoding/reverse', function () {
    it('returns address data for valid coordinates', function () {
        // Google Maps API への外部通信をフェイク
        Http::fake([
            'maps.googleapis.com/*' => Http::response([
                'results' => [
                    ['formatted_address' => '東京都千代田区丸の内1丁目'],
                ],
                'status' => 'OK',
            ]),
        ]);

        $this->postJson('/api/geocoding/reverse', [
            'latitude'  => 35.6814,
            'longitude' => 139.7671,
        ])
            ->assertOk()
            ->assertJsonStructure([
                'name',
                'address',
                'latitude',
                'longitude',
                'nearest_station',
            ]);
    });

    it('returns coordinate string when no API key', function () {
        $this->postJson('/api/geocoding/reverse', [
            'latitude'  => 35.6814,
            'longitude' => 139.7671,
        ])
            ->assertOk()
            ->assertJsonPath('latitude', 35.6814)
            ->assertJsonPath('longitude', 139.7671);
    });

    it('validates latitude range', function () {
        $this->postJson('/api/geocoding/reverse', [
            'latitude'  => 91.0,
            'longitude' => 139.7671,
        ])
            ->assertUnprocessable()
            ->assertJsonStructure(['errors' => ['latitude']]);
    });

    it('validates longitude range', function () {
        $this->postJson('/api/geocoding/reverse', [
            'latitude'  => 35.6814,
            'longitude' => 181.0,
        ])
            ->assertUnprocessable()
            ->assertJsonStructure(['errors' => ['longitude']]);
    });

    it('requires latitude and longitude', function () {
        $this->postJson('/api/geocoding/reverse', [])
            ->assertUnprocessable()
            ->assertJsonStructure(['errors' => ['latitude', 'longitude']]);
    });
});

// =========================================================
// GET /api/stations/nearest
// =========================================================

describe('GET /api/stations/nearest', function () {
    it('returns nearest WBGT station', function () {
        $this->getJson('/api/stations/nearest?lat=35.6814&lng=139.7671')
            ->assertOk()
            ->assertJsonStructure([
                'station' => ['id', 'name', 'distance', 'prefecture_code'],
            ]);
    });

    it('requires lat and lng', function () {
        $this->getJson('/api/stations/nearest')
            ->assertUnprocessable();
    });

    it('the nearest station to Tokyo is Tokyo', function () {
        $response = $this->getJson('/api/stations/nearest?lat=35.6814&lng=139.7671')
            ->assertOk();

        expect($response->json('station.name'))->toBe('東京');
    });

    it('the nearest station to Osaka is Osaka', function () {
        $response = $this->getJson('/api/stations/nearest?lat=34.6937&lng=135.5023')
            ->assertOk();

        expect($response->json('station.name'))->toBe('大阪');
    });
});
