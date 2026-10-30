<?php

namespace Tests\Feature\Auth;

use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InactiveUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_inactive_user_cannot_log_in(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ])
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Credenciales inválidas.');
    }

    public function test_inactive_user_club_token_is_rejected_before_tenant_connection(): void
    {
        $user = User::factory()->create(['is_active' => false]);
        $tenant = Tenant::factory()->create();

        TenantMembership::create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
        ]);

        $token = $user->createToken('tenant:'.$tenant->slug, ['tenant:operate']);
        $token->accessToken->forceFill(['tenant_id' => $tenant->id])->save();

        $this->withToken($token->plainTextToken)
            ->getJson('/api/me')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Usuario desactivado.');
    }
}
