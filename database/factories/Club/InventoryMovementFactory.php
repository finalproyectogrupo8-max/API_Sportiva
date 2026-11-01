<?php

namespace Database\Factories\Club;

use App\Models\Club\ClubUser;
use App\Models\Club\InventoryMovement;
use App\Models\Club\Material;
use App\Models\Club\StorageLocation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<InventoryMovement> */
class InventoryMovementFactory extends Factory
{
    protected $model = InventoryMovement::class;

    public function definition(): array
    {
        return [
            'material_id' => Material::factory(),
            'location_id' => StorageLocation::factory(),
            'movement_type' => 'in',
            'quantity' => 10.00,
            'transfer_group_id' => null,
            'order_id' => null,
            'performed_by' => function () {
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
            'reason' => 'Ingreso inicial',
            'reference' => 'REM-'.fake()->numerify('####'),
            'created_at' => now(),
        ];
    }
}
