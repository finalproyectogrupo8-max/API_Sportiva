<?php

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\Club\ClubUser;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use RuntimeException;
use Tests\Concerns\InteractsWithTenants;
use Tests\TestCase;

class TenantMembershipAdminTest extends TestCase
{
    use InteractsWithTenants;
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

    private function rawClubToken(User $user, Tenant $tenant): string
    {
        $token = $user->createToken('tenant:'.$tenant->slug, ['tenant:select', 'tenant:operate']);
        $token->accessToken->forceFill(['tenant_id' => $tenant->id])->save();

        return $token->plainTextToken;
    }

    public function test_store_creates_active_membership_with_club_roles_and_audit(): void
    {
        $admin = $this->asAdmin();
        $tenant = $this->tenantA();
        $user = User::factory()->create();

        $response = $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.11'])
            ->postJson($this->storeUrl($tenant), [
                'user_id' => $user->id,
                'roles' => ['coach', 'inventory_manager'],
            ])
            ->assertCreated()
            ->assertJsonPath('data.user_id', $user->id)
            ->assertJsonPath('data.tenant_id', $tenant->id)
            ->assertJsonPath('data.roles', [
                ['slug' => 'coach', 'name' => 'Profe'],
                ['slug' => 'inventory_manager', 'name' => 'Utilero / Encargado'],
            ])
            ->assertJsonPath('data.is_active', true)
            ->assertJsonStructure([
                'data' => ['id', 'user_id', 'tenant_id', 'roles', 'is_active', 'created_at', 'updated_at'],
            ]);

        $membership = TenantMembership::query()->sole();
        $this->assertDatabaseHas('tenant_memberships', [
            'id' => $membership->id,
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'is_active' => true,
        ]);

        // El perfil local y los roles viven en la base del club.
        $clubUser = $this->inTenant($tenant, fn () => ClubUser::query()->with('roles')->sole());
        $this->assertSame($user->id, $clubUser->global_user_id);
        $this->assertSame($user->name, $clubUser->display_name);
        $this->assertSame('active', $clubUser->status);
        $this->assertEqualsCanonicalizing(['coach', 'inventory_manager'], $clubUser->roles->pluck('slug')->all());

        [$created, $roles] = AuditLog::query()->orderBy('id')->get()->all();
        $this->assertSame($admin->id, $created->actor_id);
        $this->assertSame($response->json('data.id'), $created->subject_id);
        $this->assertSame('203.0.113.11', $created->ip);
        $this->assertSame('membership.created', $created->action);
        $this->assertSame($user->id, $created->changes['user_id']['to']);
        $this->assertSame('membership.roles_updated', $roles->action);
        $this->assertEquals(['from' => [], 'to' => ['coach', 'inventory_manager']], $roles->changes['roles']);
    }

    public function test_store_rejects_unknown_role_and_creates_nothing(): void
    {
        $this->asAdmin();
        $tenant = $this->tenantA();
        $user = User::factory()->create();

        $this->postJson($this->storeUrl($tenant), ['user_id' => $user->id, 'roles' => ['admin', 'presidente']])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('roles')
            ->assertJsonPath('errors.roles.0', 'Estos roles no existen en el club: presidente.');

        $this->assertDatabaseCount('tenant_memberships', 0);
        $this->assertDatabaseCount('audit_logs', 0);
        $this->assertSame(0, $this->inTenant($tenant, fn () => ClubUser::query()->count()));
    }

    public function test_store_requires_an_active_club(): void
    {
        $this->asAdmin();
        $tenant = Tenant::factory()->create(['status' => 'provisioning']);

        $this->postJson($this->storeUrl($tenant), ['user_id' => User::factory()->create()->id, 'roles' => ['admin']])
            ->assertUnprocessable()
            ->assertJsonPath('errors.roles.0', 'El club todavía no está activo: esperá a que termine el alta.');
    }

    public function test_store_reactivates_a_disabled_club_user(): void
    {
        $this->asAdmin();
        $tenant = $this->tenantA();
        $user = User::factory()->create();
        $this->inTenant($tenant, fn () => ClubUser::query()->create([
            'global_user_id' => $user->id,
            'display_name' => 'Nombre en el club',
            'status' => 'disabled',
        ]));

        $this->postJson($this->storeUrl($tenant), ['user_id' => $user->id, 'roles' => ['coach']])
            ->assertCreated();

        $clubUser = $this->inTenant($tenant, fn () => ClubUser::query()->sole());
        $this->assertSame('active', $clubUser->status);
        $this->assertSame('Nombre en el club', $clubUser->display_name);
    }

    public function test_store_validates_user_roles_and_required_user_id(): void
    {
        $this->asAdmin();
        $tenant = $this->tenantA();

        $this->postJson($this->storeUrl($tenant), [
            'user_id' => 999999,
            'roles' => ['admin'],
        ])->assertUnprocessable()->assertJsonValidationErrors('user_id');

        $user = User::factory()->create();
        $this->postJson($this->storeUrl($tenant), ['user_id' => $user->id])
            ->assertUnprocessable()
            ->assertJsonPath('errors.roles.0', 'Elegí al menos un rol.');
        $this->postJson($this->storeUrl($tenant), ['user_id' => $user->id, 'roles' => []])
            ->assertUnprocessable()->assertJsonValidationErrors('roles');
        $this->postJson($this->storeUrl($tenant), ['user_id' => $user->id, 'roles' => ['coach', 'coach']])
            ->assertUnprocessable()->assertJsonValidationErrors('roles.0');

        $this->postJson($this->storeUrl($tenant), ['roles' => ['admin']])
            ->assertUnprocessable()->assertJsonValidationErrors('user_id');
    }

    public function test_store_rejects_duplicate_membership_but_allows_same_user_in_another_tenant(): void
    {
        $this->asAdmin();
        $tenant = $this->tenantA();
        $otherTenant = $this->tenantB();
        $user = User::factory()->create();
        TenantMembership::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
        ]);

        $this->postJson($this->storeUrl($tenant), [
            'user_id' => $user->id,
            'roles' => ['admin'],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('user_id')
            ->assertJsonPath('errors.user_id.0', 'El usuario ya tiene una membresía en este club.');

        $this->postJson($this->storeUrl($otherTenant), [
            'user_id' => $user->id,
            'roles' => ['admin'],
        ])->assertCreated();

        $this->assertDatabaseCount('tenant_memberships', 2);
    }

    public function test_store_rejects_client_selected_active_state(): void
    {
        $this->asAdmin();
        $tenant = $this->tenantA();
        $user = User::factory()->create();

        $this->postJson($this->storeUrl($tenant), [
            'user_id' => $user->id,
            'roles' => ['admin'],
            'is_active' => false,
        ])->assertUnprocessable()->assertJsonValidationErrors('is_active');
    }

    public function test_store_returns_not_found_for_missing_or_soft_deleted_tenant(): void
    {
        $this->asAdmin();
        $user = User::factory()->create();

        $this->postJson('/api/admin/tenants/999999/memberships', [
            'user_id' => $user->id,
            'roles' => ['admin'],
        ])->assertNotFound();

        $tenant = Tenant::factory()->create();
        $tenant->delete();

        $this->postJson($this->storeUrl($tenant), [
            'user_id' => $user->id,
            'roles' => ['admin'],
        ])->assertNotFound();
    }

    public function test_update_changes_roles_and_active_state_with_partial_updates(): void
    {
        $admin = $this->asAdmin();
        $tenant = $this->tenantA();
        $user = User::factory()->create();
        $membershipId = $this->postJson($this->storeUrl($tenant), ['user_id' => $user->id, 'roles' => ['coach']])
            ->assertCreated()
            ->json('data.id');
        $membership = TenantMembership::query()->findOrFail($membershipId);
        AuditLog::query()->delete();

        $this->patchJson($this->updateUrl($tenant, $membership), ['is_active' => false])
            ->assertOk()
            ->assertJsonPath('data.is_active', false)
            ->assertJsonPath('data.roles.0.slug', 'coach');

        $this->patchJson($this->updateUrl($tenant, $membership), ['roles' => ['coordinator', 'inventory_manager']])
            ->assertOk()
            ->assertJsonPath('data.roles.0.slug', 'coordinator')
            ->assertJsonPath('data.roles.1.slug', 'inventory_manager');

        $this->assertDatabaseHas('tenant_memberships', ['id' => $membership->id, 'is_active' => false]);
        $this->assertEqualsCanonicalizing(
            ['coordinator', 'inventory_manager'],
            $this->inTenant($tenant, fn () => ClubUser::query()->sole()->roles->pluck('slug')->all()),
        );

        $log = AuditLog::query()->where('action', 'membership.roles_updated')->sole();
        $this->assertSame($admin->id, $log->actor_id);
        $this->assertEquals(['from' => ['coach'], 'to' => ['coordinator', 'inventory_manager']], $log->changes['roles']);

        // Mismos roles: no audita.
        $this->patchJson($this->updateUrl($tenant, $membership), ['roles' => ['inventory_manager', 'coordinator']])
            ->assertOk();
        $this->assertSame(1, AuditLog::query()->where('action', 'membership.roles_updated')->count());

        $this->patchJson($this->updateUrl($tenant, $membership), ['roles' => []])
            ->assertUnprocessable()->assertJsonValidationErrors('roles');
    }

    public function test_update_roles_does_not_reactivate_a_disabled_club_user(): void
    {
        $this->asAdmin();
        $tenant = $this->tenantA();
        $membership = TenantMembership::factory()->create(['tenant_id' => $tenant->id]);
        $this->inTenant($tenant, fn () => ClubUser::query()->create([
            'global_user_id' => $membership->user_id,
            'display_name' => 'Deshabilitado',
            'status' => 'disabled',
        ]));

        $this->patchJson($this->updateUrl($tenant, $membership), ['roles' => ['coach']])->assertOk();

        $this->assertSame('disabled', $this->inTenant($tenant, fn () => ClubUser::query()->sole()->status));
    }

    public function test_update_rejects_user_and_tenant_changes(): void
    {
        $this->asAdmin();
        $tenant = $this->tenantA();
        $otherTenant = $this->tenantB();
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
        $tenantA = $this->tenantA();
        $tenantB = $this->tenantB();
        $membership = TenantMembership::factory()->create([
            'tenant_id' => $tenantB->id,
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
            'roles' => ['admin'],
        ])->assertForbidden()->assertJsonPath('code', 'forbidden');
        $this->patchJson($this->updateUrl($tenant, $membership), ['is_active' => false])
            ->assertForbidden()->assertJsonPath('code', 'forbidden');
    }

    public function test_deactivation_revokes_only_tokens_for_the_membership_tenant(): void
    {
        $this->asAdmin();
        $tenantA = $this->tenantA();
        $tenantB = $this->tenantB();
        $membership = TenantMembership::factory()->create([
            'tenant_id' => $tenantA->id,
            'is_active' => true,
        ]);
        $user = $membership->user;

        $tokenA = $this->rawClubToken($user, $tenantA);
        $this->rawClubToken($user, $tenantB);
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
        $tenant = $this->tenantA();
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
        $tenant = $this->tenantA();
        $membership = TenantMembership::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'is_active' => true,
        ]);
        $token = $this->rawClubToken($user, $tenant);

        $membership->update(['is_active' => false]);

        $this->withToken($token)
            ->getJson('/api/me')
            ->assertForbidden()
            ->assertJsonPath('code', 'membership_inactive');
    }

    public function test_update_audit_records_only_changed_fields_and_skips_no_op(): void
    {
        $admin = $this->asAdmin();
        $tenant = $this->tenantA();
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
        $tenant = $this->tenantA();
        $user = User::factory()->create();

        $this->mock(AuditLogger::class)
            ->shouldReceive('recordCreated')
            ->once()
            ->andThrow(new RuntimeException('Audit storage unavailable.'));

        $this->postJson($this->storeUrl($tenant), [
            'user_id' => $user->id,
            'roles' => ['admin'],
        ])->assertInternalServerError();

        $this->assertDatabaseCount('tenant_memberships', 0);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_index_lists_tenant_memberships_with_user_data(): void
    {
        $this->asAdmin();
        $tenant = $this->tenantA();
        $otherTenant = $this->tenantB();
        $user = User::factory()->create(['name' => 'Socio Uno', 'email' => 'socio.uno@example.com']);
        $membership = TenantMembership::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'is_active' => true,
        ]);
        $this->clubToken($tenant, $user, ['maintenance_manager']);
        TenantMembership::factory()->create(['tenant_id' => $otherTenant->id]);

        $this->getJson($this->storeUrl($tenant))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $membership->id)
            ->assertJsonPath('data.0.roles', [['slug' => 'maintenance_manager', 'name' => 'Mantenimiento']])
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
        $tenant = $this->tenantA();
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
