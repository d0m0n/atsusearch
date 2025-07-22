<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'latitude',
        'longitude',
        'prefecture_code',
        'user_id',
        'is_favorite'
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'is_favorite' => 'boolean'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function wbgtData(): HasMany
    {
        return $this->hasMany(WbgtData::class);
    }

    public function getCurrentWbgtAttribute(): ?float
    {
        $currentHour = now()->hour;
        $latest = $this->wbgtData()
            ->where('date', now()->toDateString())
            ->where('hour', '<=', $currentHour)
            ->orderBy('hour', 'desc')
            ->first();
        
        return $latest ? $latest->wbgt_value : null;
    }

    public static function findNearby(float $latitude, float $longitude, int $radius = 50): \Illuminate\Database\Eloquent\Collection
    {
        return static::selectRaw('*, ( 6371 * acos( cos( radians(?) ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians(?) ) + sin( radians(?) ) * sin( radians( latitude ) ) ) ) AS distance', [$latitude, $longitude, $latitude])
            ->having('distance', '<', $radius)
            ->orderBy('distance')
            ->get();
    }
}
