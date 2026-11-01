<?php

namespace Database\Factories\Club;

use App\Models\Club\ClubUser;
use App\Models\Club\Order;
use App\Models\Club\OrderStatusHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderStatusHistory>
 */
class OrderStatusHistoryFactory extends Factory
{
    protected $model = OrderStatusHistory::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'changed_by' => function () {
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
            'old_status' => null,
            'new_status' => Order::STATUS_DRAFT,
            'comment' => 'Creación de borrador',
            'created_at' => now(),
        ];
    }
}
