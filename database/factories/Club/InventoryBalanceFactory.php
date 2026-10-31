<?php

namespace Database\Factories\Club;

use App\Models\Club\InventoryBalance;
use App\Models\Club\Material;
use App\Models\Club\StorageLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<InventoryBalance> */
class InventoryBalanceFactory extends Factory
{
    protected $model = InventoryBalance::class;

    public function definition(): array
    {
        return [
            'material_id' => Material::factory(),
            'location_id' => StorageLocation::factory(),
            'quantity' => 10.00,
            'updated_at' => now(),
        ];
    }
}
