<?php

namespace Database\Factories\Club;

use App\Models\Club\ClubUser;
use App\Models\Club\Facility;
use App\Models\Club\RepairRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<RepairRequest> */
class RepairRequestFactory extends Factory
{
    protected $model = RepairRequest::class;

    public function definition(): array
    {
        return [
            'facility_id' => Facility::factory(),
            'component_id' => null,
            'reported_by' => function () {
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
            'assigned_to' => null,
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'priority' => 'normal',
            'status' => 'open',
            'reported_at' => now(),
            'resolved_at' => null,
        ];
    }
}
