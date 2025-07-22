<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WbgtData extends Model
{
    use HasFactory;

    protected $fillable = [
        'location_id',
        'date',
        'hour',
        'wbgt_value',
        'data_type'
    ];

    protected $casts = [
        'date' => 'date',
        'hour' => 'integer',
        'wbgt_value' => 'decimal:1'
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function getWbgtLevelAttribute(): string
    {
        if (!$this->wbgt_value) {
            return 'unknown';
        }

        return match(true) {
            $this->wbgt_value >= 31 => 'danger',
            $this->wbgt_value >= 28 => 'severe_warning',
            $this->wbgt_value >= 25 => 'warning',
            $this->wbgt_value >= 21 => 'caution',
            default => 'safe'
        };
    }

    public function getWbgtLevelTextAttribute(): string
    {
        return match($this->wbgt_level) {
            'danger' => '危険：運動は原則中止',
            'severe_warning' => '厳重警戒：激しい運動は中止',
            'warning' => '警戒：積極的に休憩',
            'caution' => '注意：水分補給を忘れずに',
            'safe' => 'ほぼ安全',
            default => '不明'
        };
    }

    public function getWbgtLevelColorAttribute(): string
    {
        return match($this->wbgt_level) {
            'danger' => '#dc2626',
            'severe_warning' => '#f97316',
            'warning' => '#eab308',
            'caution' => '#3b82f6',
            'safe' => '#16a34a',
            default => '#6b7280'
        };
    }
}
