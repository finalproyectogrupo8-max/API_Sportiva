<?php

namespace Tests\Feature\Admin;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantAdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->platformAdmin()->create();
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/api/admin/tenants')->assertUnauthorized();
    }

    public function test_forbids_non_platform_admin(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson('/api/admin/tenants')
            ->assertForbidden();
    }

    public function test_platform_admin_can_list_tenants(): void
    {
        Tenant::factory()->count(2)->create();

        $this->actingAs($this->admin(), 'sanctum')
            ->getJson('/api/admin/tenants')
            ->assertOk();
    }

    public function test_store_derives_database_name_and_starts_provisioning(): void
    {
        $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/admin/tenants', ['name' => 'Club River', 'slug' => 'river'])
            ->assertCreated()
            ->assertJsonPath('database_name', 'club_river')
            ->assertJsonPath('status', 'provisioning');

        $this->assertDatabaseHas('tenants', ['slug' => 'river', 'database_name' => 'club_river']);
    }

    public function test_store_rejects_client_chosen_database_name(): void
    {
        foreach (['sportiva_platform', 'sportiva_testing', 'mysql', 'sys'] as $reserved) {
            $this->actingAs($this->admin(), 'sanctum')
                ->postJson('/api/admin/tenants', [
                    'name' => 'Club',
                    'slug' => 'river',
                    'database_name' => $reserved,
                ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('database_name');
        }

        $this->assertDatabaseCount('tenants', 0);
    }

    public function test_store_rejects_client_chosen_status(): void
    {
        $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/admin/tenants', ['name' => 'Club', 'slug' => 'river', 'status' => 'active'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');
    }

    public function test_store_validates_slug_format_length_and_uniqueness(): void
    {
        Tenant::factory()->create(['slug' => 'river', 'database_name' => 'club_river']);

        foreach (['Mi-Club', 'con espacios', str_repeat('a', 51), 'river'] as $slug) {
            $this->actingAs($this->admin(), 'sanctum')
                ->postJson('/api/admin/tenants', ['name' => 'Club', 'slug' => $slug])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('slug');
        }
    }

    public function test_update_rejects_slug_and_database_name_changes(): void
    {
        $tenant = Tenant::factory()->create(['slug' => 'river', 'database_name' => 'club_river']);

        $this->actingAs($this->admin(), 'sanctum')
            ->patchJson("/api/admin/tenants/{$tenant->id}", ['slug' => 'boca'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('slug');

        $this->actingAs($this->admin(), 'sanctum')
            ->patchJson("/api/admin/tenants/{$tenant->id}", ['database_name' => 'sportiva_platform'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('database_name');

        $this->assertDatabaseHas('tenants', ['id' => $tenant->id, 'database_name' => 'club_river']);
    }

    public function test_update_changes_name(): void
    {
        $tenant = Tenant::factory()->create();

        $this->actingAs($this->admin(), 'sanctum')
            ->patchJson("/api/admin/tenants/{$tenant->id}", ['name' => 'Nuevo nombre'])
            ->assertOk()
            ->assertJsonPath('name', 'Nuevo nombre');
    }

    public function test_is_platform_admin_is_not_mass_assignable(): void
    {
        $user = User::create([
            'name' => 'Test',
            'email' => 'test@example.com',
            'password' => 'password-123456',
            'is_platform_admin' => true,
        ]);

        $this->assertFalse($user->fresh()->is_platform_admin);
    }
}
