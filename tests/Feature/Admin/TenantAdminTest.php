<?php

namespace Tests\Feature\Admin;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
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

    public function test_forbids_non_platform_admin_on_every_endpoint(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/admin/tenants')
            ->assertForbidden()
            ->assertJsonPath('code', 'forbidden');

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/admin/tenants', ['name' => 'Club', 'slug' => 'club'])
            ->assertForbidden()
            ->assertJsonPath('code', 'forbidden');

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/admin/tenants/{$tenant->id}")
            ->assertForbidden()
            ->assertJsonPath('code', 'forbidden');

        $this->actingAs($user, 'sanctum')
            ->putJson("/api/admin/tenants/{$tenant->id}", ['name' => 'Updated'])
            ->assertForbidden()
            ->assertJsonPath('code', 'forbidden');

        $this->actingAs($user, 'sanctum')
            ->patchJson("/api/admin/tenants/{$tenant->id}", ['name' => 'Updated'])
            ->assertForbidden()
            ->assertJsonPath('code', 'forbidden');

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/admin/tenants/{$tenant->id}")
            ->assertForbidden()
            ->assertJsonPath('code', 'forbidden');
    }

    public function test_sanctum_ability_denial_uses_forbidden_api_error(): void
    {
        Route::get('/api/_test/ability', fn () => response()->noContent())
            ->middleware(['api', 'auth:sanctum', 'abilities:required']);

        $token = User::factory()->create()->createToken('test', ['other'])->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/_test/ability')
            ->assertForbidden()
            ->assertJsonPath('code', 'forbidden')
            ->assertJsonPath('message', 'No tenés permiso para esta acción.');
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

    public function test_partial_update_only_changes_sent_fields(): void
    {
        $tenant = Tenant::factory()->create(['name' => 'Original', 'status' => 'active']);
        $this->actingAs($this->admin(), 'sanctum')
            ->patchJson("/api/admin/tenants/{$tenant->id}", ['name' => 'Nuevo'])
            ->assertOk();

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'name' => 'Nuevo',
            'status' => 'active',
        ]);
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
