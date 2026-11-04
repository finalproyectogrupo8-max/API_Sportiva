<?php

namespace Database\Factories\Club;

use App\Models\Club\Position;
use App\Models\Club\Sport;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Position> */
class PositionFactory extends Factory
{
    protected $model = Position::class;

    public function definition(): array
    {
        return [
            'sport_id' => Sport::factory(),
            'name' => fake()->unique()->word(),
            'is_active' => true,
        ];
    }
}
