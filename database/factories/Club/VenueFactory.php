<?php

namespace Database\Factories\Club;

use App\Models\Club\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Venue> */
class VenueFactory extends Factory
{
    protected $model = Venue::class;

    public function definition(): array
    {
        return [
            'name' => 'Sede '.fake()->unique()->numerify('###'),
            'address' => fake()->streetAddress().', Buenos Aires',
            'latitude' => fake()->latitude(-35, -34),
            'longitude' => fake()->longitude(-59, -58),
            'is_active' => true,
        ];
    }
}
