<?php

namespace Tests\Feature\Club;

use App\Club\Roles\ManageClubRoles;
use App\Models\Club\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithTenants;
use Tests\TestCase;

/** Roles custom desde el club (docs/roles-y-permisos.md §5 y §7). */
class RoleApiTest extends TestCase
{
    use InteractsWithTenants;
    use RefreshDatabase;

    private function adminToken(): string
    {
        return $this->clubToken($this->tenantA(), roles: ['admin']);
    }

    /** @return array{name: string, description: string, permissions: list<array{name: string, scope: string}>} */
    private function payload(array $overrides = []): array
    {
        return [
            'name' => 'Profe con indumentaria',
            'description' => 'Profe que además entrega la ropa',
            'permissions' => [
                ['name' => 'players.view', 'scope' => 'own'],
                ['name' => 'apparel_orders.view', 'scope' => 'all'],
                ['name' => 'apparel_orders.deliver', 'scope' => 'all'],
            ],
            ...$overrides,
        ];
    }

    public function test_lists_standard_roles_first_with_permissions(): void
    {
        $this->withClubToken($this->adminToken())
            ->getJson('/api/roles')
            ->assertOk()
            ->assertJsonCount(7, 'data')
            ->assertJsonPath('data.0.slug', 'admin')
            ->assertJsonPath('data.0.is_system', true)
            ->assertJsonPath('data.0.users_count', 1)
            ->assertJsonPath('data.2.slug', 'sport_coordinator')
            ->assertJsonPath('data.3.slug', 'coach')
            ->assertJsonFragment(['name' => 'players.view', 'scope' => 'sport'])
            ->assertJsonFragment(['name' => 'players.view', 'scope' => 'own']);
    }

    public function test_permission_grid_groups_actions_by_module(): void
    {
        $response = $this->withClubToken($this->adminToken())
            ->getJson('/api/permissions')
            ->assertOk()
            ->assertJsonPath('data.0.key', 'jugadores')
            ->assertJsonPath('data.0.resources.0.key', 'players')
            ->assertJsonPath('data.0.resources.0.actions.0', [
                'name' => 'players.view',
                'action' => 'view',
                'label' => 'Ver',
                'scopable' => true,
            ]);

        $this->assertContains('equipo', array_column($response->json('data'), 'key'));
    }

    public function test_creates_a_custom_role_and_its_permissions_apply(): void
    {
        $this->withClubToken($this->adminToken())
            ->postJson('/api/roles', $this->payload())
            ->assertCreated()
            ->assertJsonPath('data.slug', 'profe_con_indumentaria')
            ->assertJsonPath('data.is_system', false)
            ->assertJsonPath('data.users_count', 0)
            ->assertJsonPath('data.permissions', [
                ['name' => 'apparel_orders.deliver', 'scope' => 'all'],
                ['name' => 'apparel_orders.view', 'scope' => 'all'],
                ['name' => 'players.view', 'scope' => 'own'],
            ]);

        $user = User::factory()->create();
        $token = $this->clubToken($this->tenantA(), $user, ['profe_con_indumentaria']);

        $me = $this->withClubToken($token)->getJson('/api/me')->assertOk();
        $this->assertSame(['apparel_orders.deliver', 'apparel_orders.view', 'players.view'], $me->json('permissions'));
        $this->assertSame('own', $me->json('permission_scopes')['players.view']);
    }

    public function test_validates_role_data(): void
    {
        $token = $this->adminToken();

        $this->withClubToken($token)->postJson('/api/roles', ['permissions' => []])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'permissions'])
            ->assertJsonPath('errors.permissions.0', 'Elegí al menos un permiso.');

        $this->withClubToken($token)
            ->postJson('/api/roles', $this->payload(['permissions' => [['name' => 'players.fly', 'scope' => 'all']]]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['permissions.0.name' => 'El permiso players.fly no existe.']);

        $this->withClubToken($token)
            ->postJson('/api/roles', $this->payload(['permissions' => [['name' => 'sports.view', 'scope' => 'own']]]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('permissions.0.scope');

        $this->withClubToken($token)
            ->postJson('/api/roles', $this->payload(['permissions' => [['name' => 'sports.view', 'scope' => 'some']]]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('permissions.0.scope');

        $this->withClubToken($token)
            ->postJson('/api/roles', $this->payload(['name' => 'Profe']))
            ->assertUnprocessable()
            ->assertJsonPath('errors.name.0', 'Ya existe un rol con ese nombre en el club.');

        $this->assertSame(7, $this->inTenant($this->tenantA(), fn () => Role::query()->count()));
    }

    public function test_requires_role_permissions(): void
    {
        $token = $this->clubToken($this->tenantA(), roles: ['coordinator']);

        $this->withClubToken($token)->getJson('/api/roles')->assertForbidden()->assertJsonPath('code', 'forbidden');
        $this->withClubToken($token)->postJson('/api/roles', $this->payload())->assertForbidden();
        $this->withClubToken($token)->getJson('/api/permissions')->assertForbidden();
    }

    public function test_standard_roles_cannot_be_edited_or_deleted(): void
    {
        $token = $this->adminToken();

        $this->withClubToken($token)
            ->patchJson('/api/roles/coach', ['name' => 'Entrenador'])
            ->assertForbidden()
            ->assertJsonPath('code', 'role_is_system');

        $this->withClubToken($token)
            ->deleteJson('/api/roles/coach')
            ->assertForbidden()
            ->assertJsonPath('code', 'role_is_system');

        $this->assertSame('Profe', $this->inTenant($this->tenantA(), fn () => Role::query()->where('slug', 'coach')->value('name')));
    }

    public function test_updates_a_custom_role(): void
    {
        $token = $this->adminToken();
        $this->withClubToken($token)->postJson('/api/roles', $this->payload())->assertCreated();

        $this->withClubToken($token)
            ->patchJson('/api/roles/profe_con_indumentaria', [
                'name' => 'Profe y utilero',
                'permissions' => [['name' => 'players.view', 'scope' => 'all']],
            ])
            ->assertOk()
            ->assertJsonPath('data.slug', 'profe_con_indumentaria')
            ->assertJsonPath('data.name', 'Profe y utilero')
            ->assertJsonPath('data.description', 'Profe que además entrega la ropa')
            ->assertJsonPath('data.permissions', [['name' => 'players.view', 'scope' => 'all']]);

        $this->withClubToken($token)
            ->patchJson('/api/roles/profe_con_indumentaria', ['name' => 'Profe y utilero'])
            ->assertOk();
    }

    public function test_deleting_a_role_in_use_is_rejected(): void
    {
        $token = $this->adminToken();
        $this->withClubToken($token)->postJson('/api/roles', $this->payload())->assertCreated();
        $this->clubToken($this->tenantA(), roles: ['profe_con_indumentaria']);

        $this->withClubToken($token)
            ->deleteJson('/api/roles/profe_con_indumentaria')
            ->assertConflict()
            ->assertJsonPath('code', 'role_in_use');

        $this->withClubToken($token)->postJson('/api/roles', $this->payload(['name' => 'Temporal']))->assertCreated();
        $this->withClubToken($token)->deleteJson('/api/roles/temporal')->assertNoContent();
        $this->withClubToken($token)->getJson('/api/roles/temporal')->assertNotFound();
    }

    public function test_duplicates_a_standard_role_as_custom(): void
    {
        $token = $this->adminToken();

        $copy = $this->withClubToken($token)
            ->postJson('/api/roles/coach/duplicate')
            ->assertCreated()
            ->assertJsonPath('data.name', 'Copia de Profe')
            ->assertJsonPath('data.slug', 'copia_de_profe')
            ->assertJsonPath('data.is_system', false);

        $coach = $this->withClubToken($token)->getJson('/api/roles/coach')->json('data.permissions');
        $this->assertSame($coach, $copy->json('data.permissions'));

        $this->withClubToken($token)
            ->postJson('/api/roles/coach/duplicate')
            ->assertCreated()
            ->assertJsonPath('data.name', 'Copia de Profe (2)')
            ->assertJsonPath('data.slug', 'copia_de_profe_2');
    }

    public function test_cannot_grant_permissions_or_scopes_the_user_does_not_have(): void
    {
        // Un rol custom que puede gestionar roles pero solo ve jugadores de sus categorías.
        $this->inTenant($this->tenantA(), fn () => app(ManageClubRoles::class)->create([
            'name' => 'Gestor de roles',
            'permissions' => [
                ['name' => 'roles.view', 'scope' => 'all'],
                ['name' => 'roles.create', 'scope' => 'all'],
                ['name' => 'players.view', 'scope' => 'own'],
            ],
        ]));
        $token = $this->clubToken($this->tenantA(), roles: ['gestor_de_roles']);

        $this->withClubToken($token)
            ->postJson('/api/roles', $this->payload(['permissions' => [['name' => 'players.view', 'scope' => 'all']]]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['permissions.0.name' => 'No podés dar el permiso players.view con alcance en todo el club.']);

        $this->withClubToken($token)
            ->postJson('/api/roles', $this->payload(['permissions' => [['name' => 'sports.create', 'scope' => 'all']]]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['permissions.0.name' => 'No podés dar el permiso sports.create.']);

        // Tampoco duplicando un rol con más permisos.
        $this->withClubToken($token)->postJson('/api/roles/admin/duplicate')->assertUnprocessable();

        $this->withClubToken($token)
            ->postJson('/api/roles', $this->payload(['permissions' => [['name' => 'players.view', 'scope' => 'own']]]))
            ->assertCreated();
    }

    public function test_roles_are_isolated_between_clubs(): void
    {
        $this->withClubToken($this->adminToken())->postJson('/api/roles', $this->payload())->assertCreated();
        $tokenB = $this->clubToken($this->tenantB(), roles: ['admin']);

        $this->withClubToken($tokenB)->getJson('/api/roles')->assertOk()->assertJsonCount(7, 'data');
        $this->withClubToken($tokenB)->getJson('/api/roles/profe_con_indumentaria')->assertNotFound();
        $this->withClubToken($tokenB)->deleteJson('/api/roles/profe_con_indumentaria')->assertNotFound();

        $this->assertSame(8, $this->inTenant($this->tenantA(), fn () => Role::query()->count()));
    }

    public function test_sync_keeps_custom_roles(): void
    {
        $this->withClubToken($this->adminToken())->postJson('/api/roles', $this->payload())->assertCreated();

        $this->artisan('club:sync-permissions', ['--tenant' => $this->tenantA()->slug])->assertSuccessful();

        $role = $this->inTenant($this->tenantA(), fn () => Role::query()->where('slug', 'profe_con_indumentaria')->with('permissions')->sole());
        $this->assertFalse($role->is_system);
        $this->assertCount(3, $role->permissions);
    }
}
