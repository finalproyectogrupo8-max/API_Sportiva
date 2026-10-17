<?php

namespace Database\Factories\Club;

use App\Models\Club\Sport;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Sport> */
class SportFactory extends Factory
{
    protected $model = Sport::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'is_active' => true,
        ];
    }
}
