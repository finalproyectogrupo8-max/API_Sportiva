<?php

namespace Database\Factories\Club;

use App\Models\Club\Facility;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Facility> */
class FacilityFactory extends Factory
{
    protected $model = Facility::class;

    public function definition(): array
    {
        return [
            'name' => 'Cancha '.fake()->unique()->numerify('##'),
            'type' => fake()->randomElement(['court', 'gym', 'locker_room', 'other']),
            'location' => fake()->streetAddress(),
            'status' => 'active',
            'notes' => fake()->sentence(),
        ];
    }
}
