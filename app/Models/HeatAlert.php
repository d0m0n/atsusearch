<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HeatAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'prefecture_code',
        'alert_type',
        'target_date',
        'issued_at',
        'is_active'
    ];

    protected $casts = [
        'target_date' => 'date',
        'issued_at' => 'datetime',
        'is_active' => 'boolean'
    ];

    public function getAlertLevelTextAttribute(): string
    {
        return match($this->alert_type) {
            'special_warning' => '熱中症特別警戒アラート',
            'warning' => '熱中症警戒アラート',
            default => '通常'
        };
    }

    public function getAlertLevelColorAttribute(): string
    {
        return match($this->alert_type) {
            'special_warning' => '#7c2d12',
            'warning' => '#dc2626',
            default => '#16a34a'
        };
    }

    public static function getActiveAlerts(string $prefectureCode = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = static::where('is_active', true)
            ->where('target_date', '>=', now()->toDateString());
            
        if ($prefectureCode) {
            $query->where('prefecture_code', $prefectureCode);
        }
        
        return $query->orderBy('issued_at', 'desc')->get();
    }
}
