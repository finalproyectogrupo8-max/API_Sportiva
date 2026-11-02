<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use App\Support\PasswordResetUrl;
use Illuminate\Auth\Notifications\ResetPassword as LaravelResetPassword;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Markdown;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_configuration_keeps_existing_frontend_url_and_sixty_minute_expiration(): void
    {
        $this->assertSame(
            rtrim((string) (env('FRONT_URL') ?: env('FRONTEND_URL', 'http://localhost:3000')), '/'),
            config('app.front_url'),
        );
        $this->assertSame(
            rtrim(env('FRONTEND_URL', 'http://localhost:3000'), '/'),
            config('app.frontend_url'),
        );
        $this->assertSame(60, config('auth.passwords.users.expire'));
    }

    public function test_custom_notification_uses_shared_url_and_branded_spanish_template(): void
    {
        config([
            'app.front_url' => 'https://front.example.test/',
            'app.frontend_url' => 'https://legacy.example.test',
        ]);
        $user = User::factory()->create(['email' => 'jugador@example.test']);
        $token = 'opaque-token';

        Notification::fake();
        $user->sendPasswordResetNotification($token);

        Notification::assertSentTo(
            $user,
            ResetPasswordNotification::class,
            function (ResetPasswordNotification $notification, array $channels) use ($user, $token): bool {
                $this->assertInstanceOf(ShouldQueue::class, $notification);
                $this->assertSame(['mail'], $channels);

                $mail = $notification->toMail($user);
                $expectedUrl = PasswordResetUrl::make($user, $token);
                $this->assertSame('mail.password-reset', $mail->markdown);
                $this->assertSame($expectedUrl, $mail->viewData['resetUrl']);

                parse_str((string) parse_url($expectedUrl, PHP_URL_QUERY), $query);
                $this->assertSame($token, $query['token']);
                $this->assertSame($user->email, $query['email']);

                $rendered = app(Markdown::class)->render('mail.password-reset', $mail->viewData)->toHtml();
                $this->assertStringContainsString('sportıva', $rendered);
                $this->assertStringContainsString('Elegir nueva contraseña', $rendered);
                $this->assertStringContainsString('60 minutos', $rendered);

                return true;
            },
        );
    }

    public function test_laravel_reset_notification_uses_the_same_frontend_url(): void
    {
        config(['app.front_url' => 'https://front.example.test']);
        $user = User::factory()->create(['email' => 'jugador@example.test']);
        $token = 'opaque-token';

        $mail = (new LaravelResetPassword($token))->toMail($user);

        $this->assertSame(PasswordResetUrl::make($user, $token), $mail->actionUrl);
    }
}
