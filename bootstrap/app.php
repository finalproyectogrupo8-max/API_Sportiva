<?php

use App\Http\Middleware\EnsurePlatformAdmin;
use App\Http\Middleware\EnsureTenantContext;
use App\Http\Middleware\RestrictToFrontendServer;
use App\Support\ApiError;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Laravel\Sanctum\Exceptions\MissingAbilityException;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'platform.admin' => EnsurePlatformAdmin::class,
            'tenant.token' => EnsureTenantContext::class,
            'abilities' => CheckAbilities::class,
            'ability' => CheckForAnyAbility::class,
            'frontend.restrict' => RestrictToFrontendServer::class,
        ]);

        $middleware->api(prepend: [
            RestrictToFrontendServer::class,
        ]);

        // El route model binding de los modelos de club consulta la base del club: tenant.token
        // tiene que conectarla antes.
        $middleware->prependToPriorityList(before: SubstituteBindings::class, prepend: EnsureTenantContext::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (AuthorizationException|MissingAbilityException $e, Request $request) {
            if ($request->is('api/*')) {
                return ApiError::forbidden('forbidden');
            }
        });

        // Laravel normaliza AuthorizationException a AccessDeniedHttpException antes del render.
        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return ApiError::forbidden('forbidden');
            }
        });
    })->create();
