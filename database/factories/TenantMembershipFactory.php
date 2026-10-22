<?php

namespace Database\Factories;

use App\Enums\MembershipRole;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TenantMembership>
 */
class TenantMembershipFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'tenant_id' => Tenant::factory(),
            'role' => MembershipRole::Admin->value,
            'is_active' => true,
        ];
    }
}
