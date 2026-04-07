<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class WbgtStation extends Model
{
    protected $fillable = [
        'station_code',
        'name',
        'prefecture_code',
        'latitude',
        'longitude',
        'is_active',
    ];

    protected $casts = [
        'latitude'  => 'float',
        'longitude' => 'float',
        'is_active' => 'boolean',
    ];

    /** 有効な観測地点のみを返すスコープ */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * 指定座標に最も近い観測地点を返す（Haversine近似）。
     * 距離はSQLで計算し、1件のみ取得する。
     */
    public static function nearest(float $lat, float $lon): ?self
    {
        return self::active()
            ->selectRaw(
                '*, (6371 * acos(
                    cos(radians(?)) * cos(radians(latitude)) *
                    cos(radians(longitude) - radians(?)) +
                    sin(radians(?)) * sin(radians(latitude))
                )) AS distance',
                [$lat, $lon, $lat]
            )
            ->orderBy('distance')
            ->first();
    }
}
