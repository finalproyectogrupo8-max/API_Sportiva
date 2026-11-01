<?php

namespace Database\Factories\Club;

use App\Models\Club\Material;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Material> */
class MaterialFactory extends Factory
{
    protected $model = Material::class;

    public function definition(): array
    {
        return [
            'name' => 'Material '.fake()->unique()->words(2, true),
            'description' => fake()->sentence(),
            'unit' => fake()->randomElement(['unidades', 'pares', 'pelotas', 'conos', 'metros']),
            'minimum_stock' => 5.00,
            'is_active' => true,
        ];
    }
}
