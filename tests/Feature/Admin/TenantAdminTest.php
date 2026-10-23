<?php

namespace Tests\Feature\Admin;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TenantAdminTest extends TestCase
{
    use RefreshDatabase;

    private function asAdmin(): User
    {
        $admin = User::factory()->platformAdmin()->create();
        Sanctum::actingAs($admin, ['*']);

        return $admin;
    }

    public function test_requires_authentication(): void
    {
        $tenant = Tenant::factory()->create();

        $this->getJson('/api/admin/tenants')->assertUnauthorized();
        $this->postJson('/api/admin/tenants', [])->assertUnauthorized();
        $this->getJson("/api/admin/tenants/{$tenant->id}")->assertUnauthorized();
        $this->putJson("/api/admin/tenants/{$tenant->id}", [])->assertUnauthorized();
        $this->patchJson("/api/admin/tenants/{$tenant->id}", [])->assertUnauthorized();
        $this->deleteJson("/api/admin/tenants/{$tenant->id}")->assertUnauthorized();
    }

    public function test_forbids_non_platform_admin_on_every_endpoint(): void
    {
        $tenant = Tenant::factory()->create();
        Sanctum::actingAs(User::factory()->create(), ['*']);

        $this->getJson('/api/admin/tenants')
            ->assertForbidden()->assertJsonPath('code', 'forbidden');
        $this->postJson('/api/admin/tenants', ['name' => 'Club', 'slug' => 'river'])
            ->assertForbidden()->assertJsonPath('code', 'forbidden');
        $this->getJson("/api/admin/tenants/{$tenant->id}")
            ->assertForbidden()->assertJsonPath('code', 'forbidden');
        $this->putJson("/api/admin/tenants/{$tenant->id}", ['name' => 'X'])
            ->assertForbidden()->assertJsonPath('code', 'forbidden');
        $this->patchJson("/api/admin/tenants/{$tenant->id}", ['name' => 'X'])
            ->assertForbidden()->assertJsonPath('code', 'forbidden');
        $this->deleteJson("/api/admin/tenants/{$tenant->id}")
            ->assertForbidden()->assertJsonPath('code', 'forbidden');

        $this->assertDatabaseHas('tenants', ['id' => $tenant->id, 'deleted_at' => null]);
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

    public function test_index_is_paginated_with_20_by_default(): void
    {
        Tenant::factory()->count(25)->create();
        $this->asAdmin();

        $this->getJson('/api/admin/tenants')
            ->assertOk()
            ->assertJsonCount(20, 'data')
            ->assertJsonPath('meta.per_page', 20)
            ->assertJsonPath('meta.total', 25)
            ->assertJsonStructure([
                'data' => [['id', 'slug', 'name', 'database_name', 'status']],
                'links',
                'meta',
            ]);
    }

    public function test_index_clamps_per_page_between_1_and_100(): void
    {
        Tenant::factory()->count(3)->create();
        $this->asAdmin();

        $this->getJson('/api/admin/tenants?per_page=500')
            ->assertOk()->assertJsonPath('meta.per_page', 100);
        $this->getJson('/api/admin/tenants?per_page=0')
            ->assertOk()->assertJsonPath('meta.per_page', 1);
        $this->getJson('/api/admin/tenants?per_page=abc')
            ->assertUnprocessable()->assertJsonValidationErrors('per_page');
    }

    public function test_index_filters_searches_and_sorts_with_a_whitelist(): void
    {
        Tenant::factory()->create(['slug' => 'river', 'name' => 'Club River', 'status' => 'active']);
        Tenant::factory()->create(['slug' => 'boca', 'name' => 'Club Boca', 'status' => 'suspended']);
        $this->asAdmin();

        $this->getJson('/api/admin/tenants?status=suspended')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.slug', 'boca');

        $this->getJson('/api/admin/tenants?search=Riv')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.slug', 'river');

        $this->getJson('/api/admin/tenants?sort=-name')
            ->assertOk()->assertJsonPath('data.0.slug', 'river');

        $this->getJson('/api/admin/tenants?status=cualquiera')
            ->assertUnprocessable()->assertJsonValidationErrors('status');
        $this->getJson('/api/admin/tenants?sort=password')
            ->assertUnprocessable()->assertJsonValidationErrors('sort');
    }

    public function test_store_creates_club_with_derived_database_name(): void
    {
        $this->asAdmin();

        $this->postJson('/api/admin/tenants', ['name' => 'Club River', 'slug' => 'river'])
            ->assertCreated()
            ->assertJsonPath('data.slug', 'river')
            ->assertJsonPath('data.database_name', 'club_river')
            ->assertJsonPath('data.status', 'provisioning');

        $this->assertDatabaseHas('tenants', ['slug' => 'river', 'database_name' => 'club_river']);
    }

    public function test_store_validates_name_and_slug(): void
    {
        Tenant::factory()->create(['slug' => 'river', 'database_name' => 'club_river']);
        $this->asAdmin();

        $this->postJson('/api/admin/tenants', ['slug' => 'nuevo'])
            ->assertUnprocessable()->assertJsonValidationErrors('name');

        foreach (['Mi-Club', 'con espacios', str_repeat('a', 51), 'river', ''] as $slug) {
            $this->postJson('/api/admin/tenants', ['name' => 'Club', 'slug' => $slug])
                ->assertUnprocessable()->assertJsonValidationErrors('slug');
        }
    }

    public function test_store_rejects_client_chosen_database_name_and_status(): void
    {
        $this->asAdmin();

        foreach (['sportiva_platform', 'sportiva_testing', 'mysql', 'sys'] as $reserved) {
            $this->postJson('/api/admin/tenants', [
                'name' => 'Club',
                'slug' => 'river',
                'database_name' => $reserved,
            ])->assertUnprocessable()->assertJsonValidationErrors('database_name');
        }

        $this->postJson('/api/admin/tenants', ['name' => 'Club', 'slug' => 'river', 'status' => 'active'])
            ->assertUnprocessable()->assertJsonValidationErrors('status');

        $this->assertDatabaseCount('tenants', 0);
    }

    public function test_show_returns_club_or_404(): void
    {
        $tenant = Tenant::factory()->create(['slug' => 'river']);
        $this->asAdmin();

        $this->getJson("/api/admin/tenants/{$tenant->id}")
            ->assertOk()->assertJsonPath('data.slug', 'river');

        $this->getJson('/api/admin/tenants/999999')->assertNotFound();
    }

    public function test_update_changes_name_and_toggles_status(): void
    {
        $tenant = Tenant::factory()->create(['status' => 'active']);
        $this->asAdmin();

        $this->patchJson("/api/admin/tenants/{$tenant->id}", [
            'name' => 'Nuevo nombre',
            'status' => 'suspended',
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Nuevo nombre')
            ->assertJsonPath('data.status', 'suspended');
    }

    public function test_patch_status_active_on_provisioning_tenant_is_rejected_with_422(): void
    {
        $tenant = Tenant::factory()->create(['status' => 'provisioning']);
        $this->asAdmin();

        $response = $this->patchJson("/api/admin/tenants/{$tenant->id}", ['status' => 'active']);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('status');

        $this->assertSame('provisioning', $tenant->fresh()->status);
        $this->assertDatabaseMissing('audit_logs', ['subject_id' => $tenant->id]);
    }

    public function test_patch_status_active_on_failed_tenant_is_rejected_with_422(): void
    {
        $tenant = Tenant::factory()->create(['status' => 'failed']);
        $this->asAdmin();

        $response = $this->patchJson("/api/admin/tenants/{$tenant->id}", ['status' => 'active']);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('status');

        $this->assertSame('failed', $tenant->fresh()->status);
        $this->assertDatabaseMissing('audit_logs', ['subject_id' => $tenant->id]);
    }

    public function test_patch_status_active_on_suspended_tenant_is_allowed_and_audited(): void
    {
        $admin = $this->asAdmin();
        $tenant = Tenant::factory()->create(['status' => 'suspended']);

        $response = $this->patchJson("/api/admin/tenants/{$tenant->id}", ['status' => 'active']);

        $response->assertOk()
            ->assertJsonPath('data.status', 'active');

        $this->assertSame('active', $tenant->fresh()->status);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'tenant.updated',
            'subject_id' => $tenant->id,
        ]);
    }

    public function test_patch_status_suspended_on_active_tenant_is_allowed(): void
    {
        $tenant = Tenant::factory()->create(['status' => 'active']);
        $this->asAdmin();

        $response = $this->patchJson("/api/admin/tenants/{$tenant->id}", ['status' => 'suspended']);

        $response->assertOk()
            ->assertJsonPath('data.status', 'suspended');

        $this->assertSame('suspended', $tenant->fresh()->status);
    }

    public function test_patch_status_active_on_active_tenant_returns_200_without_audit_log(): void
    {
        $tenant = Tenant::factory()->create(['status' => 'active']);
        $this->asAdmin();

        $response = $this->patchJson("/api/admin/tenants/{$tenant->id}", ['status' => 'active']);

        $response->assertOk()
            ->assertJsonPath('data.status', 'active');

        $this->assertDatabaseMissing('audit_logs', ['subject_id' => $tenant->id]);
    }

    public function test_patch_name_on_provisioning_tenant_without_status_is_allowed(): void
    {
        $tenant = Tenant::factory()->create(['status' => 'provisioning', 'name' => 'Original']);
        $this->asAdmin();

        $response = $this->patchJson("/api/admin/tenants/{$tenant->id}", ['name' => 'Modificado']);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Modificado')
            ->assertJsonPath('data.status', 'provisioning');

        $this->assertSame('Modificado', $tenant->fresh()->name);
        $this->assertSame('provisioning', $tenant->fresh()->status);
    }

    public function test_partial_update_only_changes_sent_fields(): void
    {
        $tenant = Tenant::factory()->create(['name' => 'Original', 'status' => 'active']);
        $this->asAdmin();

        $this->patchJson("/api/admin/tenants/{$tenant->id}", ['name' => 'Nuevo'])->assertOk();

        $this->assertDatabaseHas('tenants', ['id' => $tenant->id, 'name' => 'Nuevo', 'status' => 'active']);
    }

    public function test_update_rejects_immutable_fields_and_server_owned_statuses(): void
    {
        $tenant = Tenant::factory()->create(['slug' => 'river', 'database_name' => 'club_river']);
        $this->asAdmin();

        $this->patchJson("/api/admin/tenants/{$tenant->id}", ['slug' => 'boca'])
            ->assertUnprocessable()->assertJsonValidationErrors('slug');
        $this->patchJson("/api/admin/tenants/{$tenant->id}", ['database_name' => 'sportiva_platform'])
            ->assertUnprocessable()->assertJsonValidationErrors('database_name');

        foreach (['provisioning', 'failed', 'cualquiera'] as $status) {
            $this->patchJson("/api/admin/tenants/{$tenant->id}", ['status' => $status])
                ->assertUnprocessable()->assertJsonValidationErrors('status');
        }

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'slug' => 'river',
            'database_name' => 'club_river',
        ]);
    }

    public function test_destroy_soft_deletes_without_touching_the_database_name(): void
    {
        $tenant = Tenant::factory()->create(['slug' => 'river', 'database_name' => 'club_river']);
        $this->asAdmin();

        $this->deleteJson("/api/admin/tenants/{$tenant->id}")->assertNoContent();

        $this->assertSoftDeleted('tenants', ['id' => $tenant->id, 'database_name' => 'club_river']);
        $this->getJson("/api/admin/tenants/{$tenant->id}")->assertNotFound();
        $this->getJson('/api/admin/tenants')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_deleted_club_keeps_its_slug_reserved(): void
    {
        $tenant = Tenant::factory()->create(['slug' => 'river', 'database_name' => 'club_river']);
        $tenant->delete();
        $this->asAdmin();

        $this->postJson('/api/admin/tenants', ['name' => 'Otro', 'slug' => 'river'])
            ->assertUnprocessable()->assertJsonValidationErrors('slug');
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
