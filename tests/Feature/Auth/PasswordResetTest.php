<?php

namespace Tests\Feature\Auth;

use App\Models\AuditLog;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_uses_the_same_accepted_response_for_all_account_states(): void
    {
        Notification::fake();
        $activeUser = User::factory()->create(['email' => 'activo@example.test']);
        $inactiveUser = User::factory()->inactive()->create(['email' => 'inactivo@example.test']);

        $activeResponse = $this->postJson('/api/auth/forgot-password', ['email' => $activeUser->email])
            ->assertAccepted();
        $unknownResponse = $this->postJson('/api/auth/forgot-password', ['email' => 'desconocido@example.test'])
            ->assertAccepted();
        $inactiveResponse = $this->postJson('/api/auth/forgot-password', ['email' => $inactiveUser->email])
            ->assertAccepted();

        $this->assertSame($activeResponse->getContent(), $unknownResponse->getContent());
        $this->assertSame($activeResponse->getContent(), $inactiveResponse->getContent());
        Notification::assertSentTo($activeUser, ResetPasswordNotification::class);
        Notification::assertNotSentTo($inactiveUser, ResetPasswordNotification::class);
    }

    public function test_forgot_password_rejects_an_invalid_email(): void
    {
        $this->postJson('/api/auth/forgot-password', ['email' => 'no-es-un-email'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_forgot_password_is_limited_by_ip_and_normalized_email(): void
    {
        foreach (['MISMO@example.test', 'mismo@example.test', ' mismo@example.test '] as $email) {
            $this->postJson('/api/auth/forgot-password', ['email' => $email])->assertAccepted();
        }

        $this->postJson('/api/auth/forgot-password', ['email' => 'Mismo@example.test'])
            ->assertStatus(429);
    }

    public function test_forgot_password_audit_never_stores_the_email(): void
    {
        $user = User::factory()->create(['email' => 'secreto@example.test']);

        $this->postJson('/api/auth/forgot-password', ['email' => $user->email])->assertAccepted();
        $this->postJson('/api/auth/forgot-password', ['email' => 'desconocido@example.test'])->assertAccepted();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'auth.password_reset_requested',
            'subject_type' => 'user',
            'subject_id' => $user->id,
            'actor_id' => null,
            'changes' => null,
            'ip' => '127.0.0.1',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'auth.password_reset_requested',
            'subject_type' => 'auth',
            'subject_id' => 0,
            'actor_id' => null,
            'changes' => null,
            'ip' => '127.0.0.1',
        ]);
        $this->assertStringNotContainsString('secreto@example.test', AuditLog::query()->get()->toJson());
        $this->assertStringNotContainsString('desconocido@example.test', AuditLog::query()->get()->toJson());
    }

    public function test_reset_password_changes_password_rotates_remember_token_and_revokes_all_api_tokens(): void
    {
        $user = User::factory()->create();
        $oldRememberToken = $user->remember_token;
        $firstToken = $user->createToken('first')->plainTextToken;
        $user->createToken('second');
        $resetToken = Password::broker()->createToken($user);
        Event::fake([PasswordReset::class]);

        $this->postJson('/api/auth/reset-password', [
            'email' => $user->email,
            'token' => $resetToken,
            'password' => 'NuevaClave123!',
            'password_confirmation' => 'NuevaClave123!',
        ])->assertNoContent();

        $user->refresh();
        $this->assertTrue(Hash::check('NuevaClave123!', $user->password));
        $this->assertNotSame($oldRememberToken, $user->remember_token);
        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->withToken($firstToken)->getJson('/api/me/tenants')->assertUnauthorized();
        Event::assertDispatched(PasswordReset::class, fn (PasswordReset $event): bool => $event->user->is($user));
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'user.password_reset_completed',
            'subject_type' => 'user',
            'subject_id' => $user->id,
            'actor_id' => null,
            'changes' => null,
        ]);
        $auditJson = AuditLog::query()->get()->toJson();
        $this->assertStringNotContainsString('NuevaClave123!', $auditJson);
        $this->assertStringNotContainsString($user->password, $auditJson);
        $this->assertStringNotContainsString($resetToken, $auditJson);

        $this->flushHeaders();
        $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'NuevaClave123!',
        ])->assertOk();
    }

    public function test_reset_password_rejects_an_invalid_token(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/auth/reset-password', [
            'email' => $user->email,
            'token' => 'invalid-token',
            'password' => 'NuevaClave123!',
            'password_confirmation' => 'NuevaClave123!',
        ])->assertUnprocessable();
    }

    public function test_reset_password_rejects_an_expired_token(): void
    {
        $user = User::factory()->create();
        $token = Password::broker()->createToken($user);
        $this->travel(61)->minutes();

        $this->postJson('/api/auth/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'NuevaClave123!',
            'password_confirmation' => 'NuevaClave123!',
        ])->assertUnprocessable();
    }

    public function test_reset_password_rejects_a_reused_token(): void
    {
        $user = User::factory()->create();
        $token = Password::broker()->createToken($user);
        $payload = [
            'email' => $user->email,
            'token' => $token,
            'password' => 'NuevaClave123!',
            'password_confirmation' => 'NuevaClave123!',
        ];

        $this->postJson('/api/auth/reset-password', $payload)->assertNoContent();
        $payload['password'] = 'OtraClave123!';
        $payload['password_confirmation'] = 'OtraClave123!';

        $this->postJson('/api/auth/reset-password', $payload)->assertUnprocessable();
    }

    public function test_reset_password_requires_the_default_password_policy_and_confirmation(): void
    {
        $this->postJson('/api/auth/reset-password', [
            'email' => 'jugador@example.test',
            'token' => 'any-token',
            'password' => 'short',
            'password_confirmation' => 'short',
        ])->assertUnprocessable()->assertJsonValidationErrors('password');

        $this->postJson('/api/auth/reset-password', [
            'email' => 'jugador@example.test',
            'token' => 'any-token',
            'password' => 'NuevaClave123!',
            'password_confirmation' => 'OtraClave123!',
        ])->assertUnprocessable()->assertJsonValidationErrors('password');
    }

    public function test_only_platform_admins_can_initiate_an_admin_password_reset(): void
    {
        $user = User::factory()->create();
        $ordinaryUser = User::factory()->create();
        $token = $ordinaryUser->createToken('tenant', ['tenant:select'])->plainTextToken;

        $this->withToken($token)
            ->postJson("/api/admin/users/{$user->id}/send-password-reset")
            ->assertForbidden();
    }

    public function test_admin_can_send_a_new_reset_link_despite_a_recent_public_token(): void
    {
        Notification::fake();
        $admin = User::factory()->platformAdmin()->create();
        $user = User::factory()->create(['email' => 'objetivo@example.test']);
        $adminToken = $admin->createToken('platform', ['platform:admin'])->plainTextToken;

        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/auth/forgot-password', ['email' => $user->email])->assertAccepted();
        }
        $this->postJson('/api/auth/forgot-password', ['email' => $user->email])->assertStatus(429);

        $this->withToken($adminToken)
            ->postJson("/api/admin/users/{$user->id}/send-password-reset")
            ->assertAccepted();

        Notification::assertSentToTimes($user, ResetPasswordNotification::class, 2);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'user.password_reset_requested_by_admin',
            'subject_type' => 'user',
            'subject_id' => $user->id,
            'actor_id' => $admin->id,
            'changes' => null,
        ]);
        $auditJson = AuditLog::query()->get()->toJson();
        $this->assertStringNotContainsString($user->email, $auditJson);
    }

    public function test_admin_password_reset_for_inactive_user_does_not_send_or_audit(): void
    {
        Notification::fake();
        $admin = User::factory()->platformAdmin()->create();
        $user = User::factory()->inactive()->create();
        $token = $admin->createToken('platform', ['platform:admin'])->plainTextToken;

        $this->withToken($token)
            ->postJson("/api/admin/users/{$user->id}/send-password-reset")
            ->assertAccepted();

        Notification::assertNotSentTo($user, ResetPasswordNotification::class);
        $this->assertDatabaseCount('password_reset_tokens', 0);
        $this->assertDatabaseCount('audit_logs', 0);
    }
}
