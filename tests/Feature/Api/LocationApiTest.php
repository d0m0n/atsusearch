<?php

use App\Models\Location;
use App\Models\User;
use App\Services\WbgtDataService;

// =========================================================
// POST /api/locations  （未ログインでも可）
// =========================================================

describe('POST /api/locations', function () {
    beforeEach(function () {
        // WBGT外部通信をモック
        $this->mock(WbgtDataService::class, function ($mock) {
            $mock->shouldReceive('createLocationFromCoordinates')
                ->andReturn(Location::factory()->make(['id' => 1]));
        });
    });

    it('creates a location without authentication', function () {
        $this->postJson('/api/locations', [
            'name'      => '東京駅',
            'address'   => '東京都千代田区丸の内1丁目',
            'latitude'  => 35.6814,
            'longitude' => 139.7671,
        ])
            ->assertCreated()
            ->assertJsonStructure(['location', 'message']);
    });

    it('validates required latitude', function () {
        $this->postJson('/api/locations', [
            'name'      => '東京駅',
            'longitude' => 139.7671,
        ])
            ->assertUnprocessable()
            ->assertJsonStructure(['errors' => ['latitude']]);
    });

    it('validates latitude range', function () {
        $this->postJson('/api/locations', [
            'latitude'  => 91.0,
            'longitude' => 139.7671,
        ])
            ->assertUnprocessable();
    });
});

// =========================================================
// GET /api/user/locations  （要ログイン）
// =========================================================

describe('GET /api/user/locations', function () {
    it('returns 401 without authentication', function () {
        $this->getJson('/api/user/locations')
            ->assertUnauthorized();
    });

    it('returns favorite locations for authenticated user', function () {
        $user = User::factory()->create();
        Location::factory()->forUser($user->id)->favorite()->count(2)->create();
        Location::factory()->forUser($user->id)->count(1)->create(); // お気に入り外

        $this->actingAs($user)
            ->getJson('/api/user/locations')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    });

    it('does not return other users locations', function () {
        $user  = User::factory()->create();
        $other = User::factory()->create();
        Location::factory()->forUser($other->id)->favorite()->create();

        $this->actingAs($user)
            ->getJson('/api/user/locations')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    });
});

// =========================================================
// DELETE /api/user/locations/{id}  （要ログイン）
// =========================================================

describe('DELETE /api/user/locations/{id}', function () {
    it('deletes own location', function () {
        $user     = User::factory()->create();
        $location = Location::factory()->forUser($user->id)->create();

        $this->actingAs($user)
            ->deleteJson("/api/user/locations/{$location->id}")
            ->assertOk()
            ->assertJsonFragment(['message' => '地点を削除しました']);

        $this->assertModelMissing($location);
    });

    it('cannot delete another users location', function () {
        $user     = User::factory()->create();
        $other    = User::factory()->create();
        $location = Location::factory()->forUser($other->id)->create();

        $this->actingAs($user)
            ->deleteJson("/api/user/locations/{$location->id}")
            ->assertNotFound();
    });
});

// =========================================================
// PUT /api/user/settings  （要ログイン）
// =========================================================

describe('PUT /api/user/settings', function () {
    it('updates default location', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->putJson('/api/user/settings', [
                'default_latitude'  => 35.6814,
                'default_longitude' => 139.7671,
                'default_address'   => '東京都千代田区',
            ])
            ->assertOk()
            ->assertJsonFragment(['message' => '設定を更新しました']);

        expect($user->fresh()->default_address)->toBe('東京都千代田区');
    });

    it('validates latitude range', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->putJson('/api/user/settings', ['default_latitude' => 91.0])
            ->assertUnprocessable();
    });
});
