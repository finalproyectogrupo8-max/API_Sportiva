<?php

namespace Database\Factories\Club;

use App\Models\Club\Facility;
use App\Models\Club\MaintenanceRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MaintenanceRecord> */
class MaintenanceRecordFactory extends Factory
{
    protected $model = MaintenanceRecord::class;

    public function definition(): array
    {
        return [
            'repair_request_id' => null,
            'facility_id' => Facility::factory(),
            'performed_by' => null,
            'performed_at' => now(),
            'description' => fake()->sentence(),
            'cost' => fake()->randomFloat(2, 50, 1000),
        ];
    }
}
