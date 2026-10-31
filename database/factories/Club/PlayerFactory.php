<?php

namespace Database\Factories\Club;

use App\Models\Club\Player;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Player> */
class PlayerFactory extends Factory
{
    protected $model = Player::class;

    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'birth_date' => fake()->dateTimeBetween('-18 years', '-8 years')->format('Y-m-d'),
            'document_number' => null,
            'registration_number' => null,
            'preferred_position' => null,
            'dominant_side' => null,
            'status' => 'active',
            'notes' => null,
        ];
    }
}
