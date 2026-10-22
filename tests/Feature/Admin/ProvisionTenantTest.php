<?php

namespace Tests\Feature\Admin;

use App\Enums\TenantStatus;
use App\Jobs\Platform\ProvisionTenant;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProvisionTenantTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenants_create_command_registers_tenant_and_dispatches_job(): void
    {
        Queue::fake();

        $this->artisan('tenants:create', [
            'slug' => 'boca',
            'name' => 'Club Boca Juniors',
        ])->assertSuccessful();

        $this->assertDatabaseHas('tenants', [
            'slug' => 'boca',
            'name' => 'Club Boca Juniors',
            'database_name' => 'club_boca',
            'status' => TenantStatus::Provisioning->value,
        ]);

        $tenant = Tenant::where('slug', 'boca')->firstOrFail();

        Queue::assertPushed(ProvisionTenant::class, fn ($job) => $job->tenantId === $tenant->id);
    }

    public function test_tenants_store_endpoint_dispatches_provisioning_job(): void
    {
        Queue::fake();

        $admin = User::factory()->platformAdmin()->create();
        Sanctum::actingAs($admin, ['*']);

        $this->postJson('/api/admin/tenants', [
            'name' => 'Club San Lorenzo',
            'slug' => 'san_lorenzo',
        ])->assertCreated();

        $tenant = Tenant::where('slug', 'san_lorenzo')->firstOrFail();

        Queue::assertPushed(ProvisionTenant::class, fn ($job) => $job->tenantId === $tenant->id);
    }

    public function test_platform_admin_can_access_me_without_tenant_context(): void
    {
        $admin = User::factory()->platformAdmin()->create();
        Sanctum::actingAs($admin, ['*']);

        $response = $this->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('tenant', null)
            ->assertJsonPath('user.email', $admin->email)
            ->assertJsonPath('profile.role', 'platform_admin')
            ->assertJsonStructure(['user', 'tenant', 'profile', 'permissions']);

        $this->assertContains('view-dashboard', $response->json('permissions'));
    }

    public function test_regular_user_without_tenant_is_denied_on_me(): void
    {
        $user = User::factory()->create(['is_platform_admin' => false]);
        Sanctum::actingAs($user, ['*']);

        $this->getJson('/api/me')
            ->assertForbidden()
            ->assertJsonPath('code', 'tenant_required');
    }
}
