<?php

namespace App\Providers;

use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\Server;
use Illuminate\Support\ServiceProvider;

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
        // Server relativo: el contrato no depende de APP_URL (el export es igual en cualquier
        // máquina) y "Try it out" siempre llama al mismo origen que la página de docs.
        Scramble::configure()->withDocumentTransformers(function (OpenApi $openApi): void {
            $openApi->servers = [Server::make('/api')];
        });
    }
}
