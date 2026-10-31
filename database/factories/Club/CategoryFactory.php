<?php

namespace Database\Factories\Club;

use App\Models\Club\Category;
use App\Models\Club\Season;
use App\Models\Club\Sport;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Category> */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        return [
            'sport_id' => Sport::factory(),
            'season_id' => Season::factory(),
            'name' => 'Categoría '.fake()->unique()->numerify('####'),
            'min_age' => null,
            'max_age' => null,
            'is_active' => true,
        ];
    }
}
