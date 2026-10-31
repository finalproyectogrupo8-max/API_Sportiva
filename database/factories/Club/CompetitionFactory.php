<?php

namespace Database\Factories\Club;

use App\Models\Club\Competition;
use App\Models\Club\Season;
use App\Models\Club\Sport;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Competition> */
class CompetitionFactory extends Factory
{
    protected $model = Competition::class;

    public function definition(): array
    {
        return [
            'sport_id' => Sport::factory(),
            'season_id' => Season::factory(),
            'name' => 'Liga '.fake()->unique()->city(),
            'organizer' => null,
            'registration_deadline' => null,
        ];
    }
}
