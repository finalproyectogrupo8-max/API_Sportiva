<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    public function definition(): array
    {
        $slug = fake()->unique()->lexify('????????');

        return [
            'slug' => $slug,
            'name' => 'Club '.ucfirst($slug),
            'database_name' => 'club_'.$slug,
            'status' => 'active',
        ];
    }
}
