<?php

namespace App\Providers;

use App\Club\Alerts\Channels\DatabaseNotificationChannel;
use App\Club\Alerts\Channels\NotificationChannel;
use App\Models\Club\Player;
use App\Models\PersonalAccessToken;
use App\Support\PasswordResetUrl;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\Server;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Database\Eloquent\Relations\Relation;
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

        $this->app->bind(NotificationChannel::class, DatabaseNotificationChannel::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Alias estable en documents.documentable_type (TIZ-29), en lugar del nombre de la clase.
        Relation::morphMap(['player' => Player::class]);

        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);
        ResetPassword::createUrlUsing(
            fn (CanResetPassword $user, string $token): string => PasswordResetUrl::make($user, $token)
        );

        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)
            ->by(strtolower((string) $request->input('email')).'|'.$request->ip()));

        RateLimiter::for('password-reset', fn (Request $request) => Limit::perMinute(3)
            ->by(strtolower(trim((string) $request->input('email'))).'|'.$request->ip()));

        // Por email + IP: los formularios de la landing llegan desde el servidor de Next,
        // así que la IP sola sería la misma para todos los visitantes.
        RateLimiter::for('tickets', fn (Request $request) => Limit::perHour(5)
            ->by(strtolower((string) $request->input('email')).'|'.$request->ip()));

        // Server relativo: el contrato no depende de APP_URL (el export es igual en cualquier
        // máquina) y "Try it out" siempre llama al mismo origen que la página de docs.
        Scramble::configure()->withDocumentTransformers(function (OpenApi $openApi): void {
            $openApi->servers = [Server::make('/api')];
        });
    }
}
