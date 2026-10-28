<?php

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use RuntimeException;
use Tests\TestCase;

class TenantMembershipAdminTest extends TestCase
{
    use RefreshDatabase;

    private function asAdmin(): User
    {
        $admin = User::factory()->platformAdmin()->create();
        Sanctum::actingAs($admin, ['*']);

        return $admin;
    }

    private function storeUrl(Tenant $tenant): string
    {
        return "/api/admin/tenants/{$tenant->id}/memberships";
    }

    private function updateUrl(Tenant $tenant, TenantMembership $membership): string
    {
        return $this->storeUrl($tenant)."/{$membership->id}";
    }

    private function clubToken(User $user, Tenant $tenant): string
    {
        $token = $user->createToken('tenant:'.$tenant->slug, ['tenant:select', 'tenant:operate']);
        $token->accessToken->forceFill(['tenant_id' => $tenant->id])->save();

        return $token->plainTextToken;
    }

    public function test_store_creates_active_membership_with_admin_role_and_audit(): void
    {
        $admin = $this->asAdmin();
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create();

        $response = $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.11'])
            ->postJson($this->storeUrl($tenant), [
                'user_id' => $user->id,
                'role' => 'admin',
            ])
            ->assertCreated()
            ->assertJsonPath('data.user_id', $user->id)
            ->assertJsonPath('data.tenant_id', $tenant->id)
            ->assertJsonPath('data.role', 'admin')
            ->assertJsonPath('data.is_active', true)
            ->assertJsonStructure([
                'data' => ['id', 'user_id', 'tenant_id', 'role', 'is_active', 'created_at', 'updated_at'],
            ]);

        $membership = TenantMembership::query()->sole();
        $this->assertDatabaseHas('tenant_memberships', [
            'id' => $membership->id,
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'role' => 'admin',
            'is_active' => true,
        ]);

        $log = AuditLog::query()->sole();
        $this->assertSame($admin->id, $log->actor_id);
        $this->assertSame($response->json('data.id'), $log->subject_id);
        $this->assertSame('203.0.113.11', $log->ip);
        $this->assertSame('membership.created', $log->action);
        $this->assertSame($user->id, $log->changes['user_id']['to']);
    }

    public function test_store_validates_user_role_and_required_user_id(): void
    {
        $this->asAdmin();
        $tenant = Tenant::factory()->create();

        $this->postJson($this->storeUrl($tenant), [
            'user_id' => 999999,
            'role' => 'admin',
        ])->assertUnprocessable()->assertJsonValidationErrors('user_id');

        $user = User::factory()->create();
        $this->postJson($this->storeUrl($tenant), [
            'user_id' => $user->id,
            'role' => 'player',
        ])->assertUnprocessable()->assertJsonValidationErrors('role');

        $this->postJson($this->storeUrl($tenant), ['role' => 'admin'])
            ->assertUnprocessable()->assertJsonValidationErrors('user_id');
    }

    public function test_store_rejects_duplicate_membership_but_allows_same_user_in_another_tenant(): void
    {
        $this->asAdmin();
        $tenant = Tenant::factory()->create();
        $otherTenant = Tenant::factory()->create();
        $user = User::factory()->create();
        TenantMembership::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
        ]);

        $this->postJson($this->storeUrl($tenant), [
            'user_id' => $user->id,
            'role' => 'admin',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('user_id')
            ->assertJsonPath('errors.user_id.0', 'El usuario ya tiene una membresía en este club.');

        $this->postJson($this->storeUrl($otherTenant), [
            'user_id' => $user->id,
            'role' => 'admin',
        ])->assertCreated();

        $this->assertDatabaseCount('tenant_memberships', 2);
    }

    public function test_store_rejects_client_selected_active_state(): void
    {
        $this->asAdmin();
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create();

        $this->postJson($this->storeUrl($tenant), [
            'user_id' => $user->id,
            'role' => 'admin',
            'is_active' => false,
        ])->assertUnprocessable()->assertJsonValidationErrors('is_active');
    }

    public function test_store_returns_not_found_for_missing_or_soft_deleted_tenant(): void
    {
        $this->asAdmin();
        $user = User::factory()->create();

        $this->postJson('/api/admin/tenants/999999/memberships', [
            'user_id' => $user->id,
            'role' => 'admin',
        ])->assertNotFound();

        $tenant = Tenant::factory()->create();
        $tenant->delete();

        $this->postJson($this->storeUrl($tenant), [
            'user_id' => $user->id,
            'role' => 'admin',
        ])->assertNotFound();
    }

    public function test_update_changes_role_and_active_state_with_partial_updates(): void
    {
        $this->asAdmin();
        $tenant = Tenant::factory()->create();
        $membership = TenantMembership::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->patchJson($this->updateUrl($tenant, $membership), ['is_active' => false])
            ->assertOk()
            ->assertJsonPath('data.is_active', false)
            ->assertJsonPath('data.role', 'admin');

        $this->patchJson($this->updateUrl($tenant, $membership), ['role' => 'admin'])
            ->assertOk()
            ->assertJsonPath('data.role', 'admin');

        $this->assertDatabaseHas('tenant_memberships', [
            'id' => $membership->id,
            'is_active' => false,
            'role' => 'admin',
        ]);
    }

    public function test_update_rejects_user_and_tenant_changes(): void
    {
        $this->asAdmin();
        $tenant = Tenant::factory()->create();
        $otherTenant = Tenant::factory()->create();
        $membership = TenantMembership::factory()->create(['tenant_id' => $tenant->id]);
        $otherUser = User::factory()->create();

        $this->patchJson($this->updateUrl($tenant, $membership), ['user_id' => $otherUser->id])
            ->assertUnprocessable()->assertJsonValidationErrors('user_id');
        $this->patchJson($this->updateUrl($tenant, $membership), ['tenant_id' => $otherTenant->id])
            ->assertUnprocessable()->assertJsonValidationErrors('tenant_id');

        $this->assertDatabaseHas('tenant_memberships', [
            'id' => $membership->id,
            'user_id' => $membership->user_id,
            'tenant_id' => $tenant->id,
        ]);
    }

    public function test_membership_from_another_tenant_is_not_bound_or_modified(): void
    {
        $this->asAdmin();
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();
        $membership = TenantMembership::factory()->create([
            'tenant_id' => $tenantB->id,
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->patchJson($this->updateUrl($tenantA, $membership), ['is_active' => false])
            ->assertNotFound();

        $this->assertDatabaseHas('tenant_memberships', [
            'id' => $membership->id,
            'tenant_id' => $tenantB->id,
            'is_active' => true,
        ]);
    }

    public function test_requires_authentication_for_both_endpoints(): void
    {
        $tenant = Tenant::factory()->create();
        $membership = TenantMembership::factory()->create(['tenant_id' => $tenant->id]);

        $this->postJson($this->storeUrl($tenant), [])->assertUnauthorized();
        $this->patchJson($this->updateUrl($tenant, $membership), [])->assertUnauthorized();
    }

    public function test_forbids_non_platform_admin_for_both_endpoints(): void
    {
        $tenant = Tenant::factory()->create();
        $membership = TenantMembership::factory()->create(['tenant_id' => $tenant->id]);
        Sanctum::actingAs(User::factory()->create(), ['*']);

        $this->postJson($this->storeUrl($tenant), [
            'user_id' => User::factory()->create()->id,
            'role' => 'admin',
        ])->assertForbidden()->assertJsonPath('code', 'forbidden');
        $this->patchJson($this->updateUrl($tenant, $membership), ['is_active' => false])
            ->assertForbidden()->assertJsonPath('code', 'forbidden');
    }

    public function test_deactivation_revokes_only_tokens_for_the_membership_tenant(): void
    {
        $this->asAdmin();
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();
        $membership = TenantMembership::factory()->create([
            'tenant_id' => $tenantA->id,
            'is_active' => true,
        ]);
        $user = $membership->user;

        $tokenA = $this->clubToken($user, $tenantA);
        $this->clubToken($user, $tenantB);
        $user->createToken('pre-tenant');

        $this->patchJson($this->updateUrl($tenantA, $membership), ['is_active' => false])
            ->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 2);
        $this->assertDatabaseHas('personal_access_tokens', ['tenant_id' => $tenantB->id]);
        $this->assertDatabaseHas('personal_access_tokens', ['tenant_id' => null]);

        $this->app['auth']->forgetGuards();
        $this->flushHeaders();
        $this->withToken($tokenA)->getJson('/api/me/tenants')->assertUnauthorized();
    }

    public function test_reactivation_does_not_restore_revoked_tokens(): void
    {
        $this->asAdmin();
        $tenant = Tenant::factory()->create();
        $membership = TenantMembership::factory()->create([
            'tenant_id' => $tenant->id,
            'is_active' => false,
        ]);

        $this->patchJson($this->updateUrl($tenant, $membership), ['is_active' => true])
            ->assertOk()
            ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_inactive_membership_is_rejected_by_tenant_context(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::factory()->create();
        $membership = TenantMembership::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'is_active' => true,
        ]);
        $token = $this->clubToken($user, $tenant);

        $membership->update(['is_active' => false]);

        $this->withToken($token)
            ->getJson('/api/me')
            ->assertForbidden()
            ->assertJsonPath('code', 'membership_inactive');
    }

    public function test_update_audit_records_only_changed_fields_and_skips_no_op(): void
    {
        $admin = $this->asAdmin();
        $tenant = Tenant::factory()->create();
        $membership = TenantMembership::factory()->create([
            'tenant_id' => $tenant->id,
            'is_active' => true,
        ]);

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.12'])
            ->patchJson($this->updateUrl($tenant, $membership), ['is_active' => false])
            ->assertOk();

        $log = AuditLog::query()->sole();
        $this->assertSame('membership.updated', $log->action);
        $this->assertSame($admin->id, $log->actor_id);
        $this->assertSame('203.0.113.12', $log->ip);
        $this->assertSame(['is_active'], array_keys($log->changes));
        $this->assertSame(true, $log->changes['is_active']['from']);
        $this->assertSame(false, $log->changes['is_active']['to']);

        $this->patchJson($this->updateUrl($tenant, $membership), ['is_active' => false])
            ->assertOk();
        $this->assertDatabaseCount('audit_logs', 1);
    }

    public function test_audit_failure_rolls_back_membership_creation(): void
    {
        $this->asAdmin();
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create();

        $this->mock(AuditLogger::class)
            ->shouldReceive('recordCreated')
            ->once()
            ->andThrow(new RuntimeException('Audit storage unavailable.'));

        $this->postJson($this->storeUrl($tenant), [
            'user_id' => $user->id,
            'role' => 'admin',
        ])->assertInternalServerError();

        $this->assertDatabaseCount('tenant_memberships', 0);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_index_lists_tenant_memberships_with_user_data(): void
    {
        $this->asAdmin();
        $tenant = Tenant::factory()->create();
        $otherTenant = Tenant::factory()->create();
        $user = User::factory()->create(['name' => 'Socio Uno', 'email' => 'socio.uno@example.com']);
        $membership = TenantMembership::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'role' => 'admin',
            'is_active' => true,
        ]);
        TenantMembership::factory()->create(['tenant_id' => $otherTenant->id]);

        $this->getJson($this->storeUrl($tenant))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $membership->id)
            ->assertJsonPath('data.0.role', 'admin')
            ->assertJsonPath('data.0.is_active', true)
            ->assertJsonPath('data.0.user.id', $user->id)
            ->assertJsonPath('data.0.user.name', 'Socio Uno')
            ->assertJsonPath('data.0.user.email', 'socio.uno@example.com')
            ->assertJsonPath('meta.total', 1)
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    public function test_index_filters_by_active_state_and_paginates(): void
    {
        $this->asAdmin();
        $tenant = Tenant::factory()->create();
        TenantMembership::factory()->count(3)->create(['tenant_id' => $tenant->id, 'is_active' => true]);
        $inactive = TenantMembership::factory()->create(['tenant_id' => $tenant->id, 'is_active' => false]);

        $this->getJson($this->storeUrl($tenant).'?is_active=false')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $inactive->id);

        $this->getJson($this->storeUrl($tenant).'?per_page=2&page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.total', 4);

        $this->getJson($this->storeUrl($tenant).'?is_active=maybe')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('is_active');
    }

    public function test_index_returns_not_found_for_missing_or_soft_deleted_tenant(): void
    {
        $this->asAdmin();

        $this->getJson('/api/admin/tenants/999999/memberships')->assertNotFound();

        $tenant = Tenant::factory()->create();
        $tenant->delete();

        $this->getJson($this->storeUrl($tenant))->assertNotFound();
    }

    public function test_index_requires_platform_admin(): void
    {
        $tenant = Tenant::factory()->create();

        $this->getJson($this->storeUrl($tenant))->assertUnauthorized();

        Sanctum::actingAs(User::factory()->create(), ['*']);

        $this->getJson($this->storeUrl($tenant))
            ->assertForbidden()
            ->assertJsonPath('code', 'forbidden');
    }

    public function test_user_memberships_lists_the_clubs_of_a_user(): void
    {
        $this->asAdmin();
        $user = User::factory()->create();
        $club = Tenant::factory()->create(['name' => 'Villa Real']);
        $inactiveClub = Tenant::factory()->create();
        $deletedClub = Tenant::factory()->create();
        $membership = TenantMembership::factory()->create(['user_id' => $user->id, 'tenant_id' => $club->id]);
        TenantMembership::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $inactiveClub->id,
            'is_active' => false,
        ]);
        TenantMembership::factory()->create(['user_id' => $user->id, 'tenant_id' => $deletedClub->id]);
        $deletedClub->delete();
        TenantMembership::factory()->create(['tenant_id' => $club->id]);

        $this->getJson("/api/admin/users/{$user->id}/memberships")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 2);

        $this->getJson("/api/admin/users/{$user->id}/memberships?is_active=true")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $membership->id)
            ->assertJsonPath('data.0.tenant.id', $club->id)
            ->assertJsonPath('data.0.tenant.name', 'Villa Real');
    }

    public function test_user_memberships_requires_platform_admin(): void
    {
        $user = User::factory()->create();

        $this->getJson("/api/admin/users/{$user->id}/memberships")->assertUnauthorized();

        Sanctum::actingAs(User::factory()->create(), ['*']);

        $this->getJson("/api/admin/users/{$user->id}/memberships")
            ->assertForbidden()
            ->assertJsonPath('code', 'forbidden');

        $this->asAdmin();
        $this->getJson('/api/admin/users/999999/memberships')->assertNotFound();
    }
}
