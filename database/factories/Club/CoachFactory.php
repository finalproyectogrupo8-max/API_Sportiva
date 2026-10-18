<?php

namespace Database\Factories\Club;

use App\Models\Club\Coach;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Coach> */
class CoachFactory extends Factory
{
    protected $model = Coach::class;

    public function definition(): array
    {
        return [
            'club_user_id' => null,
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'phone' => fake()->optional()->phoneNumber(),
            'email' => fake()->optional()->safeEmail(),
            'status' => 'active',
        ];
    }
}
