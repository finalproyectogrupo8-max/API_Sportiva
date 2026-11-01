<?php

namespace Database\Factories\Club;

use App\Models\Club\StorageLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<StorageLocation> */
class StorageLocationFactory extends Factory
{
    protected $model = StorageLocation::class;

    public function definition(): array
    {
        return [
            'name' => 'Depósito '.fake()->unique()->words(2, true),
            'location_code' => 'DEP-'.fake()->unique()->numerify('####'),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
