<?php

namespace Database\Factories\Club;

use App\Models\Club\Category;
use App\Models\Club\ClubUser;
use App\Models\Club\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'order_number' => Order::generateOrderNumber(),
            'type' => Order::TYPE_APPAREL,
            'requested_by' => function () {
                if ($id = ClubUser::query()->value('id')) {
                    return $id;
                }
                $user = User::factory()->create();

                return ClubUser::create([
                    'global_user_id' => $user->id,
                    'display_name' => $user->name,
                    'status' => 'active',
                ])->id;
            },
            'category_id' => Category::factory(),
            'status' => Order::STATUS_DRAFT,
            'priority' => fake()->randomElement(['low', 'normal', 'high', 'urgent']),
            'description' => fake()->sentence(),
            'approved_by' => null,
            'approved_at' => null,
            'delivered_at' => null,
        ];
    }
}
