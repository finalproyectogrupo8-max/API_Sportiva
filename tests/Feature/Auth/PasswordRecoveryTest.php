<?php

namespace Tests\Feature\Auth;

use App\Models\AuditLog;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class PasswordRecoveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('forgot-password');
        RateLimiter::clear('reset-password');
    }

    public function test_forgot_password_responds_202_for_existing_active_user_and_sends_notification(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'socio@sportiva.com',
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => 'socio@sportiva.com',
        ]);

        $response->assertAccepted()
            ->assertJson([
                'message' => 'Si tu correo electrónico está registrado y activo, recibirás las instrucciones para restablecer tu contraseña.',
            ]);

        $this->assertArrayNotHasKey('token', $response->json());

        Notification::assertSentTo($user, ResetPasswordNotification::class, function (ResetPasswordNotification $notification) use ($user) {
            $mail = $notification->toMail($user);

            $this->assertSame('Restablecé tu contraseña de Sportiva', $mail->subject);

            $frontendUrl = rtrim((string) config('app.frontend_url'), '/');
            $this->assertStringStartsWith("{$frontendUrl}/restablecer-contrasena?", $mail->actionUrl);
            $this->assertStringContainsString('token='.$notification->token, $mail->actionUrl);
            $this->assertStringContainsString('email='.urlencode($user->email), $mail->actionUrl);

            $lines = implode(' ', $mail->introLines).' '.implode(' ', $mail->outroLines);
            $this->assertStringContainsString('ignorá este mensaje', $lines);
            $this->assertStringContainsString((string) config('auth.passwords.users.expire', 60), $lines);

            return true;
        });
    }

    public function test_forgot_password_responds_identical_202_for_unknown_email_and_sends_no_notification(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => 'desconocido@sportiva.com',
        ]);

        $response->assertAccepted()
            ->assertJson([
                'message' => 'Si tu correo electrónico está registrado y activo, recibirás las instrucciones para restablecer tu contraseña.',
            ]);

        Notification::assertNothingSent();
    }

    public function test_forgot_password_responds_identical_202_for_inactive_user_and_sends_no_notification(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'inactivo@sportiva.com',
            'is_active' => false,
        ]);

        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => 'inactivo@sportiva.com',
        ]);

        $response->assertAccepted()
            ->assertJson([
                'message' => 'Si tu correo electrónico está registrado y activo, recibirás las instrucciones para restablecer tu contraseña.',
            ]);

        Notification::assertNothingSent();
    }

    public function test_forgot_password_validates_email(): void
    {
        $this->postJson('/api/auth/forgot-password', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);

        $this->postJson('/api/auth/forgot-password', ['email' => 'invalido'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_forgot_password_throttles_after_five_requests(): void
    {
        Notification::fake();

        $payload = ['email' => 'rate@sportiva.com'];

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/forgot-password', $payload)->assertAccepted();
        }

        $response = $this->postJson('/api/auth/forgot-password', $payload);
        $response->assertStatus(429);
        $this->assertTrue($response->headers->has('Retry-After'));
    }

    public function test_reset_password_with_valid_token_updates_password_allows_new_login_and_fails_old(): void
    {
        $user = User::factory()->create([
            'email' => 'usuario@sportiva.com',
            'password' => 'old-password',
            'is_active' => true,
        ]);

        $token = Password::broker()->createToken($user);

        $response = $this->postJson('/api/auth/reset-password', [
            'email' => 'usuario@sportiva.com',
            'token' => $token,
            'password' => 'new-secret-password',
            'password_confirmation' => 'new-secret-password',
        ]);

        $response->assertOk()
            ->assertJson(['message' => 'Contraseña restablecida con éxito.']);

        // Login con la nueva contraseña debe funcionar
        $this->postJson('/api/auth/login', [
            'email' => 'usuario@sportiva.com',
            'password' => 'new-secret-password',
        ])->assertOk();

        // Login con la contraseña anterior debe fallar
        $this->postJson('/api/auth/login', [
            'email' => 'usuario@sportiva.com',
            'password' => 'old-password',
        ])->assertUnauthorized();
    }

    public function test_reset_password_revokes_all_user_tokens(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $user->createToken('token-1', ['tenant:select']);
        $user->createToken('token-2', ['platform:admin']);

        $this->assertCount(2, $user->fresh()->tokens);

        $token = Password::broker()->createToken($user);

        $this->postJson('/api/auth/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'brand-new-pass',
            'password_confirmation' => 'brand-new-pass',
        ])->assertOk();

        $this->assertCount(0, $user->fresh()->tokens);
    }

    public function test_reset_password_token_cannot_be_reused(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $token = Password::broker()->createToken($user);

        $payload = [
            'email' => $user->email,
            'token' => $token,
            'password' => 'brand-new-pass-1',
            'password_confirmation' => 'brand-new-pass-1',
        ];

        $this->postJson('/api/auth/reset-password', $payload)->assertOk();

        // Reutilizar el mismo token debe fallar con 422 genérico
        $this->postJson('/api/auth/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'brand-new-pass-2',
            'password_confirmation' => 'brand-new-pass-2',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_reset_password_fails_with_invalid_or_expired_token(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $token = Password::broker()->createToken($user);

        // Token inválido
        $this->postJson('/api/auth/reset-password', [
            'email' => $user->email,
            'token' => 'token-invalido',
            'password' => 'validpassword123',
            'password_confirmation' => 'validpassword123',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);

        // Token vencido (> 60 minutos)
        $this->travel(61)->minutes();

        $this->postJson('/api/auth/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'validpassword123',
            'password_confirmation' => 'validpassword123',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_reset_password_fails_if_email_does_not_match(): void
    {
        $userA = User::factory()->create(['email' => 'a@sportiva.com', 'is_active' => true]);
        $userB = User::factory()->create(['email' => 'b@sportiva.com', 'is_active' => true]);

        $tokenA = Password::broker()->createToken($userA);

        $this->postJson('/api/auth/reset-password', [
            'email' => $userB->email,
            'token' => $tokenA,
            'password' => 'validpassword123',
            'password_confirmation' => 'validpassword123',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_inactive_user_cannot_reset_password(): void
    {
        $user = User::factory()->create([
            'email' => 'inactivo@sportiva.com',
            'is_active' => false,
        ]);

        $token = Password::broker()->createToken($user);

        $this->postJson('/api/auth/reset-password', [
            'email' => 'inactivo@sportiva.com',
            'token' => $token,
            'password' => 'validpassword123',
            'password_confirmation' => 'validpassword123',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_reset_password_validates_password_confirmation_and_minimum_length(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $token = Password::broker()->createToken($user);

        // Menos de 8 caracteres
        $this->postJson('/api/auth/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'short',
            'password_confirmation' => 'short',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);

        // No coincide confirmación
        $this->postJson('/api/auth/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'validpassword123',
            'password_confirmation' => 'mismatch123',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    }

    public function test_reset_password_records_audit_log_without_secrets(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $token = Password::broker()->createToken($user);

        $this->postJson('/api/auth/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'supersecretpass123',
            'password_confirmation' => 'supersecretpass123',
        ])->assertOk();

        $log = AuditLog::query()
            ->where('subject_type', 'user')
            ->where('subject_id', $user->id)
            ->where('action', 'user.updated')
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertNull($log->actor_id);
        $this->assertSame(['password' => ['changed' => true]], $log->changes);

        // Verificamos exhaustivamente que el secreto o hash no aparezca en ningún atributo
        $serialized = json_encode($log->toArray());
        $this->assertStringNotContainsString('supersecretpass123', $serialized);
        $this->assertStringNotContainsString('$2y$', $serialized);
    }

    public function test_reset_password_throttles_after_ten_requests(): void
    {
        $payload = [
            'email' => 'rate_reset@sportiva.com',
            'token' => 'invalid-token',
            'password' => 'new-secret-password',
            'password_confirmation' => 'new-secret-password',
        ];

        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/auth/reset-password', $payload)->assertUnprocessable();
        }

        $this->postJson('/api/auth/reset-password', $payload)->assertStatus(429);
    }
}
