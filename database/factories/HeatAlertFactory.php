<?php

namespace Database\Factories;

use App\Models\HeatAlert;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<HeatAlert> */
class HeatAlertFactory extends Factory
{
    protected $model = HeatAlert::class;

    public function definition(): array
    {
        return [
            'prefecture_code' => str_pad((string) $this->faker->numberBetween(1, 47), 2, '0', STR_PAD_LEFT),
            'alert_type'      => 'warning',
            'target_date'     => now()->toDateString(),
            'issued_at'       => now()->subHour(),
            'is_active'       => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function forPrefecture(string $code): static
    {
        return $this->state(['prefecture_code' => $code]);
    }
}
