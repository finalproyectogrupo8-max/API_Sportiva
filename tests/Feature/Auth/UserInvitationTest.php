<?php

namespace Tests\Feature\Auth;

use App\Models\Club\ClubUser;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use App\Notifications\UserInvitationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\InteractsWithTenants;
use Tests\TestCase;

class UserInvitationTest extends TestCase
{
    use InteractsWithTenants;
    use RefreshDatabase;

    private function asPlatformAdmin(): User
    {
        $admin = User::factory()->platformAdmin()->create();
        Sanctum::actingAs($admin, ['platform:admin']);

        return $admin;
    }

    public function test_platform_admin_creates_user_without_password_sends_invite(): void
    {
        Notification::fake();
        $admin = $this->asPlatformAdmin();

        $response = $this->postJson('/api/admin/users', [
            'name' => 'Invitado Uno',
            'email' => 'invitado@example.test',
        ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Invitado Uno')
            ->assertJsonPath('data.email', 'invitado@example.test')
            ->assertJsonPath('data.status', 'invited')
            ->assertJsonPath('data.is_active', false);

        $user = User::query()->where('email', 'invitado@example.test')->sole();
        $this->assertFalse($user->is_active);
        $this->assertTrue($user->isInvited());
        $this->assertSame('invited', $user->status());
        $this->assertNotNull($user->invited_at);
        $this->assertNull($user->invite_accepted_at);

        Notification::assertSentTo($user, UserInvitationNotification::class, function (UserInvitationNotification $notification) {
            $this->assertNull($notification->clubName);
            $this->assertNotEmpty($notification->token);

            $mail = $notification->toMail(User::query()->where('email', 'invitado@example.test')->first());
            $this->assertSame('Te invitaron a Sportiva', $mail->subject);

            return true;
        });

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'user.invite_sent',
            'subject_id' => $user->id,
        ]);
    }

    public function test_platform_admin_creates_user_with_password_keeps_active_behavior(): void
    {
        Notification::fake();
        $admin = $this->asPlatformAdmin();

        $response = $this->postJson('/api/admin/users', [
            'name' => 'Activo Uno',
            'email' => 'activo@example.test',
            'password' => 'password123456',
        ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Activo Uno')
            ->assertJsonPath('data.email', 'activo@example.test')
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.is_active', true);

        $user = User::query()->where('email', 'activo@example.test')->sole();
        $this->assertTrue($user->is_active);
        $this->assertFalse($user->isInvited());
        $this->assertSame('active', $user->status());
        $this->assertNull($user->invited_at);
        $this->assertTrue(Hash::check('password123456', $user->password));

        Notification::assertNothingSent();

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'user.created',
            'subject_id' => $user->id,
        ]);
    }

    public function test_create_tenant_with_admin_without_password_invites_admin(): void
    {
        Notification::fake();
        $admin = $this->asPlatformAdmin();

        $this->postJson('/api/admin/tenants', [
            'name' => 'Club Atlético Norte',
            'slug' => 'norte',
            'admin' => [
                'name' => 'Admin Norte',
                'email' => 'admin@norte.test',
            ],
        ])->assertCreated();

        $tenant = Tenant::query()->where('slug', 'norte')->sole();
        $user = User::query()->where('email', 'admin@norte.test')->sole();

        $this->assertFalse($user->is_active);
        $this->assertTrue($user->isInvited());
        $this->assertSame('invited', $user->status());

        Notification::assertSentTo($user, UserInvitationNotification::class, function (UserInvitationNotification $notification) use ($tenant) {
            $this->assertSame($tenant->name, $notification->clubName);

            $mail = $notification->toMail(User::query()->where('email', 'admin@norte.test')->first());
            $this->assertSame("Te invitaron a Sportiva — {$tenant->name}", $mail->subject);

            return true;
        });

        $this->assertDatabaseHas('tenant_memberships', [
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'user.invite_sent',
            'subject_id' => $user->id,
        ]);
    }

    public function test_accept_invite_activates_user_sets_password_revokes_tokens_and_audits(): void
    {
        Notification::fake();
        $user = User::factory()->invited()->create([
            'email' => 'invitado@club.test',
            'password' => Hash::make('unusable-hash'),
        ]);

        // Existing sanctum token to check revocation
        $token = $user->createToken('test-token')->plainTextToken;
        $this->assertDatabaseCount('personal_access_tokens', 1);

        $brokerToken = Password::broker('invites')->createToken($user);

        $response = $this->postJson('/api/auth/accept-invite', [
            'email' => 'invitado@club.test',
            'token' => $brokerToken,
            'password' => 'nueva-password-1234',
            'password_confirmation' => 'nueva-password-1234',
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Invitación aceptada correctamente. Ya podés iniciar sesión.');

        $user->refresh();
        $this->assertTrue($user->is_active);
        $this->assertFalse($user->isInvited());
        $this->assertSame('active', $user->status());
        $this->assertNotNull($user->invite_accepted_at);
        $this->assertTrue(Hash::check('nueva-password-1234', $user->password));

        // Tokens revoked
        $this->assertDatabaseCount('personal_access_tokens', 0);

        // Audit log recorded
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'user.invite_accepted',
            'subject_id' => $user->id,
        ]);

        // User can now log in
        $this->postJson('/api/auth/login', [
            'email' => 'invitado@club.test',
            'password' => 'nueva-password-1234',
        ])->assertOk();
    }

    public function test_invite_token_cannot_be_reused(): void
    {
        $user = User::factory()->invited()->create([
            'email' => 'once@club.test',
        ]);
        $brokerToken = Password::broker('invites')->createToken($user);

        // First use: ok
        $this->postJson('/api/auth/accept-invite', [
            'email' => 'once@club.test',
            'token' => $brokerToken,
            'password' => 'password-primera-123',
            'password_confirmation' => 'password-primera-123',
        ])->assertOk();

        // Second use: fails with generic message
        $this->postJson('/api/auth/accept-invite', [
            'email' => 'once@club.test',
            'token' => $brokerToken,
            'password' => 'password-segunda-123',
            'password_confirmation' => 'password-segunda-123',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email')
            ->assertJsonPath('errors.email.0', 'El enlace de invitación es inválido o venció.');
    }

    public function test_invalid_or_expired_or_mismatched_token_returns_generic_422(): void
    {
        $user = User::factory()->invited()->create(['email' => 'valid@club.test']);
        $brokerToken = Password::broker('invites')->createToken($user);

        // Invalid token
        $this->postJson('/api/auth/accept-invite', [
            'email' => 'valid@club.test',
            'token' => 'invalid-token-1234',
            'password' => 'password-valida-123',
            'password_confirmation' => 'password-valida-123',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email')
            ->assertJsonPath('errors.email.0', 'El enlace de invitación es inválido o venció.');

        // Wrong email
        $this->postJson('/api/auth/accept-invite', [
            'email' => 'wrong@club.test',
            'token' => $brokerToken,
            'password' => 'password-valida-123',
            'password_confirmation' => 'password-valida-123',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email')
            ->assertJsonPath('errors.email.0', 'El enlace de invitación es inválido o venció.');

        // Expired token (72 hours + 1 minute)
        $this->travel(72 * 60 + 1)->minutes();

        $this->postJson('/api/auth/accept-invite', [
            'email' => 'valid@club.test',
            'token' => $brokerToken,
            'password' => 'password-valida-123',
            'password_confirmation' => 'password-valida-123',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email')
            ->assertJsonPath('errors.email.0', 'El enlace de invitación es inválido o venció.');
    }

    public function test_invited_user_cannot_login_or_request_password_reset(): void
    {
        Notification::fake();
        $user = User::factory()->invited()->create([
            'email' => 'pendiente@club.test',
            'password' => Hash::make('algun-secreto'),
        ]);

        // Cannot login
        $this->postJson('/api/auth/login', [
            'email' => 'pendiente@club.test',
            'password' => 'algun-secreto',
        ])
            ->assertStatus(401)
            ->assertJsonPath('message', 'Credenciales inválidas.');

        // Cannot request password reset
        $this->postJson('/api/auth/forgot-password', [
            'email' => 'pendiente@club.test',
        ])->assertStatus(202);

        Notification::assertNothingSent();
    }

    public function test_platform_resend_invite_invalidates_previous_token(): void
    {
        Notification::fake();
        $admin = $this->asPlatformAdmin();
        $user = User::factory()->invited()->create(['email' => 'resend@example.test']);

        $token1 = Password::broker('invites')->createToken($user);

        // Admin resends invite
        $this->postJson("/api/admin/users/{$user->id}/resend-invite")
            ->assertOk()
            ->assertJsonPath('message', 'Invitación reenviada correctamente.');

        Notification::assertSentTo($user, UserInvitationNotification::class, function (UserInvitationNotification $notification) use (&$token2) {
            $token2 = $notification->token;

            return true;
        });

        $this->assertNotEmpty($token2);
        $this->assertNotSame($token1, $token2);

        // Token 1 is now invalid
        $this->postJson('/api/auth/accept-invite', [
            'email' => 'resend@example.test',
            'token' => $token1,
            'password' => 'password-resend-123',
            'password_confirmation' => 'password-resend-123',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        // Token 2 works
        $this->postJson('/api/auth/accept-invite', [
            'email' => 'resend@example.test',
            'token' => $token2,
            'password' => 'password-resend-123',
            'password_confirmation' => 'password-resend-123',
        ])->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'user.invite_resent',
            'subject_id' => $user->id,
        ]);
    }

    public function test_resend_invite_on_active_user_returns_409_conflict(): void
    {
        $this->asPlatformAdmin();
        $user = User::factory()->create(['is_active' => true]);

        $this->postJson("/api/admin/users/{$user->id}/resend-invite")
            ->assertStatus(409)
            ->assertJsonPath('code', 'not_invited');
    }

    public function test_club_resend_invite_requires_permission_and_club_membership(): void
    {
        Notification::fake();
        $adminUser = User::factory()->create(['email' => 'admin@club.test']);
        $adminToken = $this->clubToken($this->tenantA(), $adminUser, ['admin']);

        $invitedUser = User::factory()->invited()->create(['email' => 'coach@club.test']);
        $tenantAMember = $this->inTenant($this->tenantA(), function () use ($invitedUser) {
            return ClubUser::query()->create([
                'global_user_id' => $invitedUser->id,
                'display_name' => 'Coach Invitado',
                'status' => 'active',
            ]);
        });
        TenantMembership::query()->create([
            'tenant_id' => $this->tenantA()->id,
            'user_id' => $invitedUser->id,
            'is_active' => true,
        ]);

        // Resend on tenant A by admin succeeds
        $this->withClubToken($adminToken)
            ->postJson("/api/club-users/{$tenantAMember->id}/resend-invite")
            ->assertOk()
            ->assertJsonPath('message', 'Invitación reenviada correctamente.');

        Notification::assertSentTo($invitedUser, UserInvitationNotification::class, function (UserInvitationNotification $notification) {
            $this->assertSame($this->tenantA()->name, $notification->clubName);

            return true;
        });

        // User without club_users.update permission gives 403
        $normalUser = User::factory()->create();
        $normalToken = $this->clubToken($this->tenantA(), $normalUser, ['coach']);
        $this->withClubToken($normalToken)
            ->postJson("/api/club-users/{$tenantAMember->id}/resend-invite")
            ->assertForbidden();

        // Resend for member of another club gives 404
        $tenantBAdmin = User::factory()->create();
        $tenantBToken = $this->clubToken($this->tenantB(), $tenantBAdmin, ['admin']);
        $this->withClubToken($tenantBToken)
            ->postJson("/api/club-users/{$tenantAMember->id}/resend-invite")
            ->assertNotFound();
    }

    public function test_resend_invite_throttle(): void
    {
        $this->asPlatformAdmin();
        $user = User::factory()->invited()->create();

        // 3 calls within the hour succeed
        $this->postJson("/api/admin/users/{$user->id}/resend-invite")->assertOk();
        $this->postJson("/api/admin/users/{$user->id}/resend-invite")->assertOk();
        $this->postJson("/api/admin/users/{$user->id}/resend-invite")->assertOk();

        // 4th call gives 429 Too Many Requests
        $this->postJson("/api/admin/users/{$user->id}/resend-invite")->assertStatus(429);
    }
}
