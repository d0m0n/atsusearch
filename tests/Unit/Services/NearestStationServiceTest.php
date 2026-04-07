<?php

use App\Services\NearestStationService;

beforeEach(function () {
    $this->service = new NearestStationService();
});

// =========================================================
// findNearest()
// =========================================================

describe('NearestStationService::findNearest()', function () {
    it('returns a station for valid coordinates', function () {
        $result = $this->service->findNearest(35.6814, 139.7671);

        expect($result)->toBeArray()
            ->toHaveKeys(['id', 'name', 'distance', 'prefecture_code']);
    });

    it('returns Tokyo station for Tokyo coordinates', function () {
        $result = $this->service->findNearest(35.6814, 139.7671);

        expect($result['name'])->toBe('東京');
        expect($result['prefecture_code'])->toBe('13');
    });

    it('returns Osaka station for Osaka coordinates', function () {
        $result = $this->service->findNearest(34.6937, 135.5023);

        expect($result['name'])->toBe('大阪');
        expect($result['prefecture_code'])->toBe('27');
    });

    it('returns Sapporo station for Sapporo coordinates', function () {
        $result = $this->service->findNearest(43.0642, 141.3469);

        expect($result['name'])->toBe('札幌');
        expect($result['prefecture_code'])->toBe('01');
    });

    it('returns Naha station for Okinawa coordinates', function () {
        $result = $this->service->findNearest(26.2128, 127.6792);

        expect($result['name'])->toBe('那覇');
        expect($result['prefecture_code'])->toBe('47');
    });

    it('returns a positive distance in km', function () {
        $result = $this->service->findNearest(35.6814, 139.7671);

        expect($result['distance'])->toBeFloat()->toBeGreaterThanOrEqual(0);
    });

    it('distance is close to zero for exact station coordinates', function () {
        // 東京駅の座標（47662 の緯度経度に近い）
        $result = $this->service->findNearest(35.6814, 139.7671);

        expect($result['distance'])->toBeLessThan(5.0); // 5km以内
    });
});

// =========================================================
// findById()
// =========================================================

describe('NearestStationService::findById()', function () {
    it('returns station by ID', function () {
        $station = $this->service->findById('47662'); // 東京

        expect($station)->toBeArray()
            ->toHaveKeys(['id', 'name', 'lat', 'lon', 'prefecture_code']);
        expect($station['name'])->toBe('東京');
    });

    it('returns null for unknown ID', function () {
        $result = $this->service->findById('99999');

        expect($result)->toBeNull();
    });
});

// =========================================================
// getAllStations()
// =========================================================

describe('NearestStationService::getAllStations()', function () {
    it('returns all 47 stations', function () {
        $stations = $this->service->getAllStations();

        expect($stations)->toHaveCount(47);
    });

    it('each station has required keys', function () {
        $stations = $this->service->getAllStations();

        foreach ($stations as $station) {
            expect($station)->toHaveKeys(['id', 'name', 'lat', 'lon', 'prefecture_code']);
        }
    });

    it('prefecture codes are 2-digit strings', function () {
        $stations = $this->service->getAllStations();

        foreach ($stations as $station) {
            expect($station['prefecture_code'])->toMatch('/^\d{2}$/');
        }
    });
});
