<?php

namespace Database\Factories;

use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Location> */
class LocationFactory extends Factory
{
    protected $model = Location::class;

    private static array $cities = [
        ['name' => '東京',  'lat' => 35.6814, 'lon' => 139.7671, 'pref' => '13'],
        ['name' => '大阪',  'lat' => 34.6937, 'lon' => 135.5023, 'pref' => '27'],
        ['name' => '名古屋','lat' => 35.1815, 'lon' => 136.9066, 'pref' => '23'],
        ['name' => '福岡',  'lat' => 33.5904, 'lon' => 130.4017, 'pref' => '40'],
        ['name' => '札幌',  'lat' => 43.0642, 'lon' => 141.3469, 'pref' => '01'],
    ];

    public function definition(): array
    {
        $city = $this->faker->randomElement(self::$cities);

        return [
            'name'            => $city['name'],
            'address'         => $city['name'] . '付近',
            'latitude'        => $city['lat'] + $this->faker->randomFloat(4, -0.05, 0.05),
            'longitude'       => $city['lon'] + $this->faker->randomFloat(4, -0.05, 0.05),
            'prefecture_code' => $city['pref'],
            'user_id'         => null,
            'is_favorite'     => false,
        ];
    }

    public function favorite(): static
    {
        return $this->state(['is_favorite' => true]);
    }

    public function forUser(int $userId): static
    {
        return $this->state(['user_id' => $userId]);
    }
}
