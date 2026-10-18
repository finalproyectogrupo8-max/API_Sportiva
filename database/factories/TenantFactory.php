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
        // test_*: en testing, TenantDatabase solo acepta bases club_test_* (las de un club real, nunca).
        $slug = 'test_'.fake()->unique()->lexify('????????');

        return [
            'slug' => $slug,
            'name' => 'Club '.ucfirst($slug),
            'database_name' => 'club_'.$slug,
            'status' => 'active',
        ];
    }
}
