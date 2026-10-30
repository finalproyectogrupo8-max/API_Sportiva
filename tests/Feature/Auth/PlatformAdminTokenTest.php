<?php

namespace Tests\Feature\Auth;

use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithTenants;
use Tests\TestCase;

class PlatformAdminTokenTest extends TestCase
{
    use InteractsWithTenants;
    use RefreshDatabase;

    private function resetAuthContext(): static
    {
        $this->app['auth']->forgetGuards();
        $this->flushHeaders();

        return $this;
    }

    public function test_admin_login_includes_platform_token_and_persists_in_database(): void
    {
        $admin = User::factory()->platformAdmin()->create([
            'email' => 'admin@sportiva.test',
            'password' => 'secret-password-12',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@sportiva.test',
            'password' => 'secret-password-12',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'token',
                'expires_at',
                'user',
                'tenants',
                'platform_token',
                'platform_expires_at',
            ]);

        $platformToken = $response->json('platform_token');
        $platformExpiresAt = $response->json('platform_expires_at');

        $this->assertNotNull($platformToken);
        $this->assertNotNull($platformExpiresAt);
        $this->assertStringContainsString('|sportiva_', $platformToken);

        // Verificar registro en personal_access_tokens
        $this->assertDatabaseHas('personal_access_tokens', [
            'name' => 'platform',
            'tokenable_id' => $admin->id,
            'tenant_id' => null,
            'abilities' => json_encode(['platform:admin']),
        ]);

        $expectedExpiry = now()->addMinutes(config('sanctum.platform_ttl'));
        $actualExpiry = Carbon::parse($platformExpiresAt);
        $this->assertTrue($actualExpiry->diffInSeconds($expectedExpiry) < 5);
    }

    public function test_non_admin_login_does_not_include_platform_token_and_does_not_create_it(): void
    {
        $user = User::factory()->create([
            'is_platform_admin' => false,
            'email' => 'user@sportiva.test',
            'password' => 'secret-password-12',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'user@sportiva.test',
            'password' => 'secret-password-12',
        ]);

        $response->assertOk();
        $this->assertArrayNotHasKey('platform_token', $response->json());
        $this->assertArrayNotHasKey('platform_expires_at', $response->json());

        // Asegurar que no se creó ningún token con ability platform:admin
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'abilities' => json_encode(['platform:admin']),
        ]);
    }

    public function test_admin_tenants_with_pre_tenant_token_returns_403_forbidden(): void
    {
        $admin = User::factory()->platformAdmin()->create(['password' => 'secret-password-12']);

        $loginRes = $this->postJson('/api/auth/login', [
            'email' => $admin->email,
            'password' => 'secret-password-12',
        ])->assertOk();

        $preTenantToken = $loginRes->json('token');

        $this->resetAuthContext();

        $response = $this->withToken($preTenantToken)->getJson('/api/admin/tenants');

        $response->assertForbidden()
            ->assertExactJson([
                'message' => 'No tenés permiso para esta acción.',
                'code' => 'forbidden',
            ]);
    }

    public function test_admin_tenants_with_club_token_returns_403_forbidden(): void
    {
        $admin = User::factory()->platformAdmin()->create(['password' => 'secret-password-12']);
        // Club de prueba con base migrada: select-tenant lee la base del club.
        $club = tap($this->tenantA())->update(['slug' => 'river']);

        TenantMembership::create([
            'user_id' => $admin->id,
            'tenant_id' => $club->id,
            'is_active' => true,
        ]);

        $loginRes = $this->postJson('/api/auth/login', [
            'email' => $admin->email,
            'password' => 'secret-password-12',
        ])->assertOk();

        $preTenantToken = $loginRes->json('token');

        $this->resetAuthContext();

        $selectRes = $this->withToken($preTenantToken)
            ->postJson('/api/auth/select-tenant', ['tenant' => 'river'])
            ->assertOk();

        $clubToken = $selectRes->json('token');

        $this->resetAuthContext();

        $response = $this->withToken($clubToken)->getJson('/api/admin/tenants');

        $response->assertForbidden()
            ->assertExactJson([
                'message' => 'No tenés permiso para esta acción.',
                'code' => 'forbidden',
            ]);
    }

    public function test_admin_tenants_with_platform_token_returns_200(): void
    {
        $admin = User::factory()->platformAdmin()->create(['password' => 'secret-password-12']);

        $loginRes = $this->postJson('/api/auth/login', [
            'email' => $admin->email,
            'password' => 'secret-password-12',
        ])->assertOk();

        $platformToken = $loginRes->json('platform_token');

        $this->resetAuthContext();

        $response = $this->withToken($platformToken)->getJson('/api/admin/tenants');

        $response->assertOk()
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    public function test_non_admin_with_forced_platform_admin_ability_returns_403_forbidden(): void
    {
        $user = User::factory()->create(['is_platform_admin' => false]);
        $forcedToken = $user->createToken('forged-platform', ['platform:admin']);

        $this->resetAuthContext();

        $response = $this->withToken($forcedToken->plainTextToken)->getJson('/api/admin/tenants');

        $response->assertForbidden()
            ->assertExactJson([
                'message' => 'No tenés permiso para esta acción.',
                'code' => 'forbidden',
            ]);
    }

    public function test_platform_token_on_club_and_select_tenant_endpoints_is_rejected(): void
    {
        $admin = User::factory()->platformAdmin()->create(['password' => 'secret-password-12']);

        $loginRes = $this->postJson('/api/auth/login', [
            'email' => $admin->email,
            'password' => 'secret-password-12',
        ])->assertOk();

        $platformToken = $loginRes->json('platform_token');

        // 1. En endpoint de club (/api/me exige tenant context y tenant_id en el token)
        $this->resetAuthContext();
        $this->withToken($platformToken)
            ->getJson('/api/me')
            ->assertForbidden()
            ->assertJsonPath('code', 'tenant_required');

        // 2. En select-tenant (exige ability tenant:select)
        $this->resetAuthContext();
        $this->withToken($platformToken)
            ->postJson('/api/auth/select-tenant', ['tenant' => 'river'])
            ->assertForbidden()
            ->assertExactJson([
                'message' => 'No tenés permiso para esta acción.',
                'code' => 'forbidden',
            ]);
    }

    public function test_platform_token_lifetime_remains_valid_after_16_minutes_and_expires_after_ttl(): void
    {
        $admin = User::factory()->platformAdmin()->create(['password' => 'secret-password-12']);

        $loginRes = $this->postJson('/api/auth/login', [
            'email' => $admin->email,
            'password' => 'secret-password-12',
        ])->assertOk();

        $platformToken = $loginRes->json('platform_token');

        // Pasados 16 minutos: el token pre-tenant (15 min) ya habría vencido, pero el de plataforma (480 min) sigue válido
        $this->travel(16)->minutes();
        $this->resetAuthContext();

        $this->withToken($platformToken)->getJson('/api/admin/tenants')->assertOk();

        // Pasado el TTL completo + 1 minuto: debe vencer (401)
        $ttl = (int) config('sanctum.platform_ttl');
        $this->travel($ttl - 16 + 1)->minutes();
        $this->resetAuthContext();

        $this->withToken($platformToken)->getJson('/api/admin/tenants')->assertUnauthorized();
    }

    public function test_deactivating_admin_or_changing_password_revokes_platform_token(): void
    {
        $superAdmin = User::factory()->platformAdmin()->create();
        $targetAdmin = User::factory()->platformAdmin()->create(['password' => 'initial-password-12']);

        // 1. Obtener platform_token para targetAdmin
        $loginRes = $this->postJson('/api/auth/login', [
            'email' => $targetAdmin->email,
            'password' => 'initial-password-12',
        ])->assertOk();

        $platformToken = $loginRes->json('platform_token');

        // Funciona inicialmente
        $this->resetAuthContext();
        $this->withToken($platformToken)->getJson('/api/admin/tenants')->assertOk();

        // 2. Desactivar a targetAdmin mediante superAdmin
        $superToken = $superAdmin->createToken('platform', ['platform:admin'])->plainTextToken;
        $this->resetAuthContext();
        $this->withToken($superToken)
            ->patchJson("/api/admin/users/{$targetAdmin->id}", ['is_active' => false])
            ->assertOk();

        // El token anterior de targetAdmin debe ser rechazado
        $this->resetAuthContext();
        $this->withToken($platformToken)->getJson('/api/admin/tenants')->assertUnauthorized();
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $targetAdmin->id,
            'name' => 'platform',
        ]);

        // 3. Reactivar targetAdmin y probar revocación por cambio de contraseña
        $this->resetAuthContext();
        $targetAdmin->forceFill(['is_active' => true])->save();
        $newLoginRes = $this->postJson('/api/auth/login', [
            'email' => $targetAdmin->email,
            'password' => 'initial-password-12',
        ])->assertOk();

        $newToken = $newLoginRes->json('platform_token');
        $this->resetAuthContext();
        $this->withToken($newToken)->getJson('/api/admin/tenants')->assertOk();

        // Cambiar contraseña vía PATCH
        $this->resetAuthContext();
        $this->withToken($superToken)
            ->patchJson("/api/admin/users/{$targetAdmin->id}", ['password' => 'nueva-clave-segura-123'])
            ->assertOk();

        // El token debe estar revocado
        $this->resetAuthContext();
        $this->withToken($newToken)->getJson('/api/admin/tenants')->assertUnauthorized();
    }

    public function test_admin_endpoint_forbidden_response_structure_is_exact(): void
    {
        $user = User::factory()->create(['is_platform_admin' => false]);
        $token = $user->createToken('user-token', ['tenant:select'])->plainTextToken;

        $this->resetAuthContext();

        $response = $this->withToken($token)->getJson('/api/admin/tenants');

        $response->assertStatus(403)
            ->assertExactJson([
                'message' => 'No tenés permiso para esta acción.',
                'code' => 'forbidden',
            ]);
    }

    public function test_login_exposes_is_platform_admin_true_for_admins(): void
    {
        User::factory()->platformAdmin()->create([
            'email' => 'admin@sportiva.test',
            'password' => 'secret-password-12',
        ]);

        $this->postJson('/api/auth/login', [
            'email' => 'admin@sportiva.test',
            'password' => 'secret-password-12',
        ])->assertOk()->assertJsonPath('user.is_platform_admin', true);
    }

    public function test_login_exposes_is_platform_admin_false_for_regular_users(): void
    {
        User::factory()->create([
            'email' => 'socio@sportiva.test',
            'password' => 'secret-password-12',
        ]);

        $this->postJson('/api/auth/login', [
            'email' => 'socio@sportiva.test',
            'password' => 'secret-password-12',
        ])->assertOk()->assertJsonPath('user.is_platform_admin', false);
    }
}
