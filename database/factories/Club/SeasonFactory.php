<?php

namespace Database\Factories\Club;

use App\Models\Club\Season;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Season> */
class SeasonFactory extends Factory
{
    protected $model = Season::class;

    public function definition(): array
    {
        $year = fake()->numberBetween(2020, 2035);

        return [
            'name' => "Season {$year}",
            'start_date' => "{$year}-01-01",
            'end_date' => "{$year}-12-31",
            'status' => 'planned',
        ];
    }
}
