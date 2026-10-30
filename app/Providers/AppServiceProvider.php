<?php

namespace App\Providers;

use App\Models\PersonalAccessToken;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\Server;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Sin /docs/api: la documentación se registra en routes/api.php como /api/docs.
        Scramble::ignoreDefaultRoutes();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)
            ->by(strtolower((string) $request->input('email')).'|'.$request->ip()));

        // Por email + IP: los formularios de la landing llegan desde el servidor de Next,
        // así que la IP sola sería la misma para todos los visitantes.
        RateLimiter::for('tickets', fn (Request $request) => Limit::perHour(5)
            ->by(strtolower((string) $request->input('email')).'|'.$request->ip()));

        RateLimiter::for('forgot-password', fn (Request $request) => [
            Limit::perHour(5)->by($request->ip()),
            Limit::perHour(5)->by(strtolower((string) $request->input('email'))),
        ]);

        RateLimiter::for('reset-password', fn (Request $request) => [
            Limit::perHour(10)->by($request->ip()),
            Limit::perHour(10)->by(strtolower((string) $request->input('email'))),
        ]);

        ResetPassword::createUrlUsing(function (object $notifiable, string $token): string {
            $frontendUrl = rtrim((string) config('app.frontend_url', 'http://localhost:3000'), '/');
            $email = method_exists($notifiable, 'getEmailForPasswordReset')
                ? $notifiable->getEmailForPasswordReset()
                : $notifiable->email;

            return $frontendUrl.'/restablecer-contrasena?'.http_build_query([
                'token' => $token,
                'email' => $email,
            ]);
        });

        // Server relativo: el contrato no depende de APP_URL (el export es igual en cualquier
        // máquina) y "Try it out" siempre llama al mismo origen que la página de docs.
        Scramble::configure()->withDocumentTransformers(function (OpenApi $openApi): void {
            $openApi->servers = [Server::make('/api')];
        });
    }
}
