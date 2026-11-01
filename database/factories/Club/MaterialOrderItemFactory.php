<?php

namespace Database\Factories\Club;

use App\Models\Club\Material;
use App\Models\Club\MaterialOrderItem;
use App\Models\Club\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MaterialOrderItem>
 */
class MaterialOrderItemFactory extends Factory
{
    protected $model = MaterialOrderItem::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'material_id' => Material::factory(),
            'quantity' => fake()->randomFloat(2, 1, 50),
            'delivered_quantity' => 0,
            'notes' => null,
        ];
    }
}
