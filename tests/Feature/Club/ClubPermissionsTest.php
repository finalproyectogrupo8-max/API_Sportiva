<?php

namespace Tests\Feature\Club;

use App\Club\Access\ClubMembers;
use App\Models\Club\Category;
use App\Models\Club\ClubUser;
use App\Models\Club\Coach;
use App\Models\Club\Sport;
use App\Models\TenantMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\InteractsWithTenants;
use Tests\TestCase;

/**
 * Roles y permisos del club (docs/roles-y-permisos.md): casos 8 (permisos) y 10 (aislamiento entre
 * clubes) de la guía ABM.
 */
class ClubPermissionsTest extends TestCase
{
    use InteractsWithTenants;
    use RefreshDatabase;

    public function test_coach_can_read_categories_but_cannot_create_sports(): void
    {
        $token = $this->clubToken($this->tenantA(), roles: ['coach']);

        $this->withClubToken($token)->getJson('/api/categories')->assertOk();

        $this->withClubToken($token)
            ->postJson('/api/sports', ['name' => 'Hockey'])
            ->assertForbidden()
            ->assertJsonPath('code', 'forbidden');

        $this->assertSame(0, $this->inTenant($this->tenantA(), fn () => Sport::query()->count()));
    }

    public function test_club_admin_can_manage_sports(): void
    {
        $token = $this->clubToken($this->tenantA(), roles: ['admin']);

        $id = $this->withClubToken($token)
            ->postJson('/api/sports', ['name' => 'Hockey'])
            ->assertCreated()
            ->json('data.id');

        $this->withClubToken($token)->patchJson("/api/sports/{$id}", ['name' => 'Hockey césped'])->assertOk();
        $this->withClubToken($token)->deleteJson("/api/sports/{$id}")->assertNoContent();
    }

    public function test_member_without_club_profile_has_no_permissions(): void
    {
        $token = $this->clubToken($this->tenantA());

        $this->withClubToken($token)
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('profile', null)
            ->assertJsonPath('permissions', []);

        $this->withClubToken($token)->getJson('/api/sports')->assertForbidden();
    }

    public function test_me_returns_profile_roles_and_merged_permissions(): void
    {
        $user = User::factory()->create(['name' => 'Ana Profe']);
        $token = $this->clubToken($this->tenantA(), $user, ['coach', 'inventory_manager']);

        $response = $this->withClubToken($token)
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('profile.display_name', 'Ana Profe')
            ->assertJsonPath('profile.status', 'active')
            ->assertJsonPath('profile.roles', [
                ['slug' => 'coach', 'name' => 'Profe'],
                ['slug' => 'inventory_manager', 'name' => 'Utilero / Encargado'],
            ]);

        $permissions = $response->json('permissions');
        $scopes = $response->json('permission_scopes');

        // Los permisos de los dos roles se suman.
        $this->assertContains('players.view', $permissions);
        $this->assertContains('inventory.adjust', $permissions);
        $this->assertNotContains('sports.create', $permissions);
        $this->assertSame(array_keys($scopes), $permissions);

        // Solo del profe: sus categorías. En los dos roles: gana todo el club.
        $this->assertSame('own', $scopes['players.view']);
        $this->assertSame('all', $scopes['apparel_orders.view']);
        $this->assertSame('all', $scopes['material_orders.create']);
    }

    public function test_role_changes_apply_on_the_next_request(): void
    {
        $user = User::factory()->create();
        $token = $this->clubToken($this->tenantA(), $user, ['coordinator']);

        $this->withClubToken($token)->postJson('/api/sports', ['name' => 'Rugby'])->assertCreated();

        $this->inTenant($this->tenantA(), fn () => app(ClubMembers::class)->ensure($user, ['coach']));

        $this->withClubToken($token)->postJson('/api/sports', ['name' => 'Vóley'])->assertForbidden();
    }

    public function test_disabled_club_user_is_rejected_even_with_active_membership(): void
    {
        $user = User::factory()->create(['password' => 'password']);
        $token = $this->clubToken($this->tenantA(), $user, ['admin']);
        $this->inTenant($this->tenantA(), fn () => ClubUser::query()->update(['status' => 'disabled']));

        $this->withClubToken($token)
            ->getJson('/api/sports')
            ->assertForbidden()
            ->assertJsonPath('code', 'club_user_disabled');

        $pre = $this->withClubToken('')
            ->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'password'])
            ->assertOk()
            ->json('token');

        $this->withClubToken($pre)
            ->postJson('/api/auth/select-tenant', ['tenant' => $this->tenantA()->slug])
            ->assertForbidden()
            ->assertJsonPath('code', 'club_user_disabled');
    }

    public function test_roles_are_isolated_between_clubs(): void
    {
        $user = User::factory()->create();
        $tokenA = $this->clubToken($this->tenantA(), $user, ['admin']);
        $tokenB = $this->clubToken($this->tenantB(), $user, ['coach']);

        $this->withClubToken($tokenA)->postJson('/api/sports', ['name' => 'Hockey'])->assertCreated();
        $this->withClubToken($tokenB)->postJson('/api/sports', ['name' => 'Hockey'])->assertForbidden();

        $this->withClubToken($tokenB)
            ->getJson('/api/me')
            ->assertJsonPath('tenant.slug', $this->tenantB()->slug)
            ->assertJsonPath('profile.roles.0.slug', 'coach');

        $this->assertSame(1, $this->inTenant($this->tenantA(), fn () => Sport::query()->count()));
        $this->assertSame(0, $this->inTenant($this->tenantB(), fn () => Sport::query()->count()));
    }

    public function test_club_profile_of_another_club_does_not_grant_access(): void
    {
        // El club_user de A tiene el mismo global_user_id, pero en B el usuario no tiene perfil.
        $user = User::factory()->create();
        $this->clubToken($this->tenantA(), $user, ['admin']);
        $tokenB = $this->clubToken($this->tenantB(), $user);

        $this->withClubToken($tokenB)->getJson('/api/sports')->assertForbidden();
        $this->withClubToken($tokenB)->getJson('/api/me')->assertJsonPath('permissions', []);
    }

    public function test_coach_only_sees_own_coach_profile_and_colleagues_of_own_categories(): void
    {
        $user = User::factory()->create();
        $token = $this->clubToken($this->tenantA(), $user, ['coach']);

        [$colleague, $stranger] = $this->inTenant($this->tenantA(), function () use ($user) {
            $clubUser = ClubUser::query()->where('global_user_id', $user->id)->sole();
            $own = Category::factory()->create(['name' => 'U21']);
            $other = Category::factory()->create(['name' => 'First']);

            $me = Coach::factory()->create(['club_user_id' => $clubUser->id, 'last_name' => 'Yo']);
            $colleague = Coach::factory()->create(['last_name' => 'Colega']);
            $stranger = Coach::factory()->create(['last_name' => 'Ajeno']);
            $former = Coach::factory()->create(['last_name' => 'Anterior']);

            $assign = fn (Coach $coach, Category $category, ?string $end = null) => DB::connection('tenant')
                ->table('category_coaches')
                ->insert([
                    'category_id' => $category->id,
                    'coach_id' => $coach->id,
                    'assignment_type' => 'head_coach',
                    'end_date' => $end,
                ]);

            $assign($me, $own);
            $assign($colleague, $own);
            $assign($stranger, $other);
            $assign($former, $own, now()->subMonth()->toDateString());

            return [$colleague, $stranger];
        });

        $this->withClubToken($token)
            ->getJson('/api/coaches?sort=last_name')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.last_name', 'Colega')
            ->assertJsonPath('data.1.last_name', 'Yo');

        $this->withClubToken($token)->getJson("/api/coaches/{$colleague->id}")->assertOk();
        $this->withClubToken($token)->getJson("/api/coaches/{$stranger->id}")->assertNotFound();

        // Con alcance "todo el club", el coordinador ve a todos.
        $coordinator = $this->clubToken($this->tenantA(), roles: ['coordinator']);
        $this->withClubToken($coordinator)->getJson('/api/coaches')->assertJsonCount(4, 'data');
    }

    public function test_tenants_migrate_seeds_roles_without_duplicates_and_backfills_admins(): void
    {
        $tenant = $this->tenantA();
        $legacyAdmin = User::factory()->create();
        TenantMembership::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $legacyAdmin->id]);
        TenantMembership::factory()->create(['tenant_id' => $tenant->id, 'is_active' => false]);

        $counts = fn () => $this->inTenant($tenant, fn () => [
            DB::connection('tenant')->table('roles')->count(),
            DB::connection('tenant')->table('permissions')->count(),
            DB::connection('tenant')->table('role_permissions')->count(),
            ClubUser::query()->count(),
        ]);

        $this->artisan('tenants:migrate', ['--tenant' => $tenant->slug])->assertSuccessful();
        $first = $counts();
        $this->artisan('tenants:migrate', ['--tenant' => $tenant->slug])->assertSuccessful();

        $this->assertSame($first, $counts());
        $this->assertSame(7, $first[0]);
        $this->assertSame(1, $first[3]);

        // La membresía activa sin perfil (anterior a los roles) queda como Administrador del club.
        $roles = $this->inTenant($tenant, fn () => ClubUser::query()
            ->where('global_user_id', $legacyAdmin->id)->sole()->roles->pluck('slug')->all());
        $this->assertSame(['admin'], $roles);
    }
}
