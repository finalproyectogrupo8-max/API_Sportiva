<?php

namespace Database\Factories\Club;

use App\Models\Club\Category;
use App\Models\Club\GameMatch;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<GameMatch> */
class GameMatchFactory extends Factory
{
    protected $model = GameMatch::class;

    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'opponent' => 'Club '.fake()->lastName(),
            'match_date' => fake()->dateTimeBetween('+1 day', '+30 days')->format('Y-m-d H:00:00'),
            'is_home' => fake()->boolean(),
            'status' => 'scheduled',
        ];
    }
}
