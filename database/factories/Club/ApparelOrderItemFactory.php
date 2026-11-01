<?php

namespace Database\Factories\Club;

use App\Models\Club\ApparelItem;
use App\Models\Club\ApparelOrderItem;
use App\Models\Club\Order;
use App\Models\Club\Player;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApparelOrderItem>
 */
class ApparelOrderItemFactory extends Factory
{
    protected $model = ApparelOrderItem::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'player_id' => Player::factory(),
            'apparel_item_id' => ApparelItem::factory(),
            'size' => fake()->randomElement(['S', 'M', 'L', 'XL', '14', '16']),
            'shirt_number' => fake()->numberBetween(1, 99),
            'alias' => fake()->firstName(),
            'quantity' => 1,
            'delivered_quantity' => 0,
            'notes' => null,
        ];
    }
}
