<?php

namespace Tests\Feature\Auth;

use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    /** El guard de Sanctum cachea el usuario entre requests del mismo test: se resetea. */
    private function api(?string $token = null): static
    {
        $this->app['auth']->forgetGuards();
        $this->flushHeaders();

        return $token ? $this->withToken($token) : $this;
    }

    private function loginToken(User $user): string
    {
        return $this->api()
            ->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'password'])
            ->assertOk()
            ->json('token');
    }

    private function joinClub(User $user, array $tenant = [], array $membership = []): Tenant
    {
        $club = Tenant::factory()->create($tenant);

        TenantMembership::create([
            'user_id' => $user->id,
            'tenant_id' => $club->id,
            'role' => 'admin',
            ...$membership,
        ]);

        return $club;
    }

    public function test_login_returns_pre_tenant_token_and_active_clubs(): void
    {
        $user = User::factory()->create();
        $river = $this->joinClub($user, ['slug' => 'river', 'name' => 'Club River']);
        $this->joinClub($user, ['slug' => 'boca'], ['is_active' => false]);
        $this->joinClub($user, ['slug' => 'racing', 'status' => 'failed']);
        Tenant::factory()->create(['slug' => 'ajeno']);

        $response = $this->api()
            ->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'password'])
            ->assertOk()
            ->assertJsonPath('user.email', $user->email)
            ->assertJsonCount(1, 'tenants')
            ->assertJsonPath('tenants.0.slug', $river->slug)
            ->assertJsonStructure(['token', 'expires_at', 'user' => ['id', 'name', 'email'], 'tenants']);

        $this->assertStringContainsString('|sportiva_', $response->json('token'));
        $this->assertDatabaseHas('personal_access_tokens', ['tokenable_id' => $user->id, 'tenant_id' => null]);
    }

    public function test_login_fails_with_same_message_for_unknown_email_and_wrong_password(): void
    {
        $user = User::factory()->create();

        $wrongPassword = $this->api()
            ->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'incorrecta'])
            ->assertUnauthorized();

        $unknownEmail = $this->api()
            ->postJson('/api/auth/login', ['email' => 'nadie@example.com', 'password' => 'password'])
            ->assertUnauthorized();

        $this->assertSame($wrongPassword->json('message'), $unknownEmail->json('message'));
    }

    public function test_login_is_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->api()->postJson('/api/auth/login', ['email' => 'x@example.com', 'password' => 'mal']);
        }

        $this->api()
            ->postJson('/api/auth/login', ['email' => 'x@example.com', 'password' => 'mal'])
            ->assertStatus(429);
    }

    public function test_select_tenant_swaps_pre_tenant_token_for_club_token(): void
    {
        $user = User::factory()->create();
        $club = $this->joinClub($user, ['slug' => 'river']);
        $pre = $this->loginToken($user);

        $response = $this->api($pre)
            ->postJson('/api/auth/select-tenant', ['tenant' => 'river'])
            ->assertOk()
            ->assertJsonPath('tenant.slug', 'river')
            ->assertJsonStructure(['token', 'expires_at', 'tenant', 'profile', 'permissions']);

        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->assertDatabaseHas('personal_access_tokens', ['tenant_id' => $club->id]);

        $this->api($pre)->getJson('/api/me/tenants')->assertUnauthorized();
        $this->api($response->json('token'))->getJson('/api/me')->assertOk();
    }

    public function test_select_tenant_denies_without_active_membership(): void
    {
        $user = User::factory()->create();
        Tenant::factory()->create(['slug' => 'ajeno']);
        $this->joinClub($user, ['slug' => 'inactiva'], ['is_active' => false]);
        $pre = $this->loginToken($user);

        foreach (['ajeno', 'inactiva', 'no_existe'] as $slug) {
            $this->api($pre)
                ->postJson('/api/auth/select-tenant', ['tenant' => $slug])
                ->assertForbidden()
                ->assertJsonPath('code', 'membership_inactive');
        }
    }

    public function test_select_tenant_denies_inactive_club(): void
    {
        $user = User::factory()->create();
        $this->joinClub($user, ['slug' => 'river', 'status' => 'active']);
        $pre = $this->loginToken($user);

        Tenant::where('slug', 'river')->update(['status' => 'suspended']);

        $this->api($pre)
            ->postJson('/api/auth/select-tenant', ['tenant' => 'river'])
            ->assertForbidden()
            ->assertJsonPath('code', 'tenant_inactive');
    }

    public function test_select_tenant_requires_authentication(): void
    {
        $this->api()->postJson('/api/auth/select-tenant', ['tenant' => 'river'])->assertUnauthorized();
    }

    public function test_pre_tenant_token_cannot_use_club_endpoints(): void
    {
        $user = User::factory()->create();
        $this->joinClub($user);

        $this->api($this->loginToken($user))
            ->getJson('/api/me')
            ->assertForbidden()
            ->assertJsonPath('code', 'tenant_required');
    }

    public function test_club_token_can_switch_club(): void
    {
        $user = User::factory()->create();
        $this->joinClub($user, ['slug' => 'river']);
        $this->joinClub($user, ['slug' => 'boca']);

        $river = $this->api($this->loginToken($user))
            ->postJson('/api/auth/select-tenant', ['tenant' => 'river'])
            ->json('token');

        $boca = $this->api($river)
            ->postJson('/api/auth/select-tenant', ['tenant' => 'boca'])
            ->assertOk()
            ->json('token');

        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->api($river)->getJson('/api/me')->assertUnauthorized();
        $this->api($boca)->getJson('/api/me')->assertOk()->assertJsonPath('tenant.slug', 'boca');
    }

    public function test_membership_is_revalidated_on_every_request(): void
    {
        $user = User::factory()->create();
        $this->joinClub($user, ['slug' => 'river']);

        $token = $this->api($this->loginToken($user))
            ->postJson('/api/auth/select-tenant', ['tenant' => 'river'])
            ->json('token');

        TenantMembership::where('user_id', $user->id)->update(['is_active' => false]);

        $this->api($token)
            ->getJson('/api/me')
            ->assertForbidden()
            ->assertJsonPath('code', 'membership_inactive');
    }

    public function test_me_tenants_works_with_pre_tenant_token(): void
    {
        $user = User::factory()->create();
        $this->joinClub($user, ['slug' => 'river']);

        $this->api($this->loginToken($user))
            ->getJson('/api/me/tenants')
            ->assertOk()
            ->assertJsonPath('tenants.0.slug', 'river');
    }

    public function test_logout_revokes_the_token(): void
    {
        $user = User::factory()->create();
        $token = $this->loginToken($user);

        $this->api($token)->postJson('/api/auth/logout')->assertNoContent();

        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->api($token)->getJson('/api/me/tenants')->assertUnauthorized();
    }

    public function test_pre_tenant_token_expires(): void
    {
        $user = User::factory()->create();
        $token = $this->loginToken($user);

        $this->travel(config('sanctum.pre_tenant_ttl') + 1)->minutes();

        $this->api($token)->getJson('/api/me/tenants')->assertUnauthorized();
    }

    public function test_create_admin_command_sets_platform_admin_explicitly(): void
    {
        $this->artisan('platform:create-admin', ['email' => 'admin@sportiva.test', '--name' => 'Admin'])
            ->expectsQuestion('Contraseña (mínimo 12 caracteres)', 'una-clave-larga-123')
            ->expectsQuestion('Repetir contraseña', 'una-clave-larga-123')
            ->assertSuccessful();

        $this->assertTrue(User::where('email', 'admin@sportiva.test')->firstOrFail()->is_platform_admin);
    }
}
