<?php

namespace Database\Factories\Club;

use App\Models\Club\Facility;
use App\Models\Club\FacilityComponent;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FacilityComponent> */
class FacilityComponentFactory extends Factory
{
    protected $model = FacilityComponent::class;

    public function definition(): array
    {
        return [
            'facility_id' => Facility::factory(),
            'name' => fake()->randomElement(['Aro de básquet', 'Red de vóley', 'Iluminación principal', 'Piso parquet', 'Tablero marcador']),
            'condition_status' => 'ok',
            'last_inspected_at' => now(),
            'notes' => fake()->sentence(),
        ];
    }
}
