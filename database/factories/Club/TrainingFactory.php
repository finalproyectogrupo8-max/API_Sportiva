<?php

namespace Database\Factories\Club;

use App\Models\Club\Category;
use App\Models\Club\Training;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Training> */
class TrainingFactory extends Factory
{
    protected $model = Training::class;

    public function definition(): array
    {
        $start = fake()->dateTimeBetween('+1 day', '+30 days')->format('Y-m-d 18:00:00');

        return [
            'category_id' => Category::factory(),
            'starts_at' => $start,
            'ends_at' => date('Y-m-d H:i:s', strtotime($start.' +90 minutes')),
        ];
    }
}
