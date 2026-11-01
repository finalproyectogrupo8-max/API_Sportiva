<?php

namespace Database\Factories\Club;

use App\Models\Club\ApparelItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApparelItem>
 */
class ApparelItemFactory extends Factory
{
    protected $model = ApparelItem::class;

    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Camiseta Titular', 'Camiseta Suplente', 'Short de Juego', 'Campera de Abrigo', 'Pantalón Largo']),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
