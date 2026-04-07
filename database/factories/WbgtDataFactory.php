<?php

namespace Database\Factories;

use App\Models\Location;
use App\Models\WbgtData;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<WbgtData> */
class WbgtDataFactory extends Factory
{
    protected $model = WbgtData::class;

    public function definition(): array
    {
        $hour = $this->faker->numberBetween(0, 23);

        // 時間帯に応じたリアルなWBGT値
        $base = match (true) {
            $hour < 6           => $this->faker->randomFloat(1, 16, 20),
            $hour < 9           => $this->faker->randomFloat(1, 20, 24),
            $hour < 12          => $this->faker->randomFloat(1, 24, 28),
            $hour < 15          => $this->faker->randomFloat(1, 28, 34),
            $hour < 18          => $this->faker->randomFloat(1, 26, 31),
            $hour < 21          => $this->faker->randomFloat(1, 22, 27),
            default             => $this->faker->randomFloat(1, 18, 23),
        };

        return [
            'location_id' => Location::factory(),
            'date'        => now()->toDateString(),
            'hour'        => $hour,
            'wbgt_value'  => $base,
            'data_type'   => $this->faker->randomElement(['forecast', 'actual']),
            'data_source' => 'sample',
            'fetch_time'  => now(),
        ];
    }

    public function forecast(): static
    {
        return $this->state(['data_type' => 'forecast']);
    }

    public function actual(): static
    {
        return $this->state(['data_type' => 'actual']);
    }

    public function danger(): static
    {
        return $this->state(['wbgt_value' => $this->faker->randomFloat(1, 31, 40)]);
    }
}
