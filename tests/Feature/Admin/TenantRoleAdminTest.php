<?php

namespace Tests\Feature\Admin;

use App\Models\Club\Role;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\InteractsWithTenants;
use Tests\TestCase;

/** Roles de un club desde el panel de plataforma y la regla del último administrador. */
class TenantRoleAdminTest extends TestCase
{
    use InteractsWithTenants;
    use RefreshDatabase;

    private function asAdmin(): User
    {
        $admin = User::factory()->platformAdmin()->create();
        Sanctum::actingAs($admin, ['*']);

        return $admin;
    }

    private function url(Tenant $tenant, string $path = ''): string
    {
        return "/api/admin/tenants/{$tenant->id}/roles{$path}";
    }

    /** Miembro con roles en el club, como lo deja el panel. */
    private function member(Tenant $tenant, array $roles, bool $active = true): TenantMembership
    {
        $user = User::factory()->create();
        $this->clubToken($tenant, $user, $roles);

        $membership = TenantMembership::query()->where('user_id', $user->id)->where('tenant_id', $tenant->id)->sole();
        $membership->update(['is_active' => $active]);

        return $membership;
    }

    public function test_platform_admin_manages_custom_roles_of_a_club(): void
    {
        $this->asAdmin();
        $tenant = $this->tenantA();

        $this->getJson("/api/admin/tenants/{$tenant->id}/permissions")
            ->assertOk()
            ->assertJsonPath('data.0.key', 'jugadores');

        // Sin el límite de "solo dar lo que tenés": el admin de plataforma prepara el club.
        $this->postJson($this->url($tenant), [
            'name' => 'Tesorero',
            'permissions' => [['name' => 'club_users.view', 'scope' => 'all'], ['name' => 'inventory.view', 'scope' => 'all']],
        ])
            ->assertCreated()
            ->assertJsonPath('data.slug', 'tesorero')
            ->assertJsonPath('data.users_count', 0)
            ->assertJsonCount(2, 'data.permissions');

        $this->getJson($this->url($tenant, '/tesorero'))->assertOk()->assertJsonPath('data.name', 'Tesorero');

        $this->patchJson($this->url($tenant, '/tesorero'), ['description' => 'Cuentas del club'])
            ->assertOk()
            ->assertJsonPath('data.description', 'Cuentas del club')
            ->assertJsonCount(2, 'data.permissions');

        $this->postJson($this->url($tenant, '/maintenance_manager/duplicate'))
            ->assertCreated()
            ->assertJsonPath('data.name', 'Copia de Mantenimiento');

        $this->getJson($this->url($tenant))->assertOk()->assertJsonCount(9, 'data');

        $this->deleteJson($this->url($tenant, '/tesorero'))->assertNoContent();
        $this->getJson($this->url($tenant, '/tesorero'))->assertNotFound();

        // Solo en el club A.
        $this->getJson($this->url($this->tenantB()))->assertOk()->assertJsonCount(7, 'data');
    }

    public function test_admin_endpoints_keep_role_rules(): void
    {
        $this->asAdmin();
        $tenant = $this->tenantA();

        $this->patchJson($this->url($tenant, '/admin'), ['name' => 'Presidente'])
            ->assertForbidden()
            ->assertJsonPath('code', 'role_is_system');

        $this->postJson($this->url($tenant), ['name' => 'Profe', 'permissions' => [['name' => 'players.view', 'scope' => 'all']]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');

        $this->postJson($this->url($tenant), ['name' => 'Ayudante', 'permissions' => [['name' => 'players.view', 'scope' => 'own']]])
            ->assertCreated();
        $this->member($tenant, ['ayudante']);

        $this->deleteJson($this->url($tenant, '/ayudante'))->assertConflict()->assertJsonPath('code', 'role_in_use');
        $this->getJson($this->url($tenant, '/no_existe'))->assertNotFound();
    }

    public function test_inactive_club_has_no_roles_to_manage(): void
    {
        $this->asAdmin();
        $tenant = Tenant::factory()->create(['status' => 'provisioning']);

        $this->getJson($this->url($tenant))->assertOk()->assertJsonCount(0, 'data');
        $this->postJson($this->url($tenant), ['name' => 'Tesorero', 'permissions' => [['name' => 'inventory.view', 'scope' => 'all']]])
            ->assertConflict()
            ->assertJsonPath('code', 'tenant_inactive');
    }

    public function test_requires_platform_admin(): void
    {
        $tenant = $this->tenantA();

        $this->getJson($this->url($tenant))->assertUnauthorized();

        Sanctum::actingAs(User::factory()->create(), ['*']);
        $this->getJson($this->url($tenant))->assertForbidden();
        $this->postJson($this->url($tenant), [])->assertForbidden();
        $this->deleteJson($this->url($tenant, '/coach'))->assertForbidden();
    }

    public function test_last_active_admin_cannot_lose_the_admin_role_or_access(): void
    {
        $this->asAdmin();
        $tenant = $this->tenantA();
        $admin = $this->member($tenant, ['admin']);
        $membershipUrl = "/api/admin/tenants/{$tenant->id}/memberships/{$admin->id}";

        $this->patchJson($membershipUrl, ['roles' => ['coordinator']])
            ->assertConflict()
            ->assertJsonPath('code', 'last_admin');
        $this->patchJson($membershipUrl, ['is_active' => false])
            ->assertConflict()
            ->assertJsonPath('code', 'last_admin');

        // Sumar roles sin sacar admin, sí.
        $this->patchJson($membershipUrl, ['roles' => ['admin', 'coach']])->assertOk();

        // Un admin con la membresía desactivada no cuenta.
        $this->member($tenant, ['admin'], active: false);
        $this->patchJson($membershipUrl, ['roles' => ['coordinator']])->assertConflict();

        // Con otro admin activo, ya se puede.
        $this->member($tenant, ['admin']);
        $this->patchJson($membershipUrl, ['roles' => ['coordinator']])->assertOk();
        $this->patchJson($membershipUrl, ['is_active' => false])->assertOk();

        $this->assertSame(['coordinator'], $this->inTenant($tenant, fn () => Role::query()
            ->whereHas('clubUsers', fn ($query) => $query->where('global_user_id', $admin->user_id))
            ->pluck('slug')
            ->all()));
    }
}
