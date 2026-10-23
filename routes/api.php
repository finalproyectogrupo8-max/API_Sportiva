<?php

use App\Http\Controllers\Admin\TenantController;
use App\Http\Controllers\Admin\TenantMembershipController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Club\CategoryController;
use App\Http\Controllers\Club\CoachController;
use App\Http\Controllers\Club\SeasonController;
use App\Http\Controllers\Club\SportController;
use App\Http\Controllers\HealthController;
use Dedoc\Scramble\Scramble;
use Illuminate\Support\Facades\Route;

// Todas las rutas de este archivo quedan bajo el prefijo /api (ver bootstrap/app.php).

Route::get('/health', HealthController::class)->name('health');

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('select-tenant', [AuthController::class, 'selectTenant'])
            ->middleware('abilities:tenant:select');
        Route::post('logout', [AuthController::class, 'logout']);
    });
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('me/tenants', [AuthController::class, 'meTenants']);
    Route::get('me', [AuthController::class, 'me'])->middleware('tenant.token');
});

Route::middleware(['auth:sanctum', 'abilities:platform:admin', 'platform.admin'])
    ->prefix('admin')
    ->group(function () {
        Route::post('tenants/{tenant}/retry', [TenantController::class, 'retry']);
        Route::apiResource('tenants', TenantController::class);
        Route::apiResource('users', UserController::class)->except(['destroy']);
        Route::post('tenants/{tenant}/memberships', [TenantMembershipController::class, 'store']);
        Route::patch('tenants/{tenant}/memberships/{membership}', [
            TenantMembershipController::class,
            'update',
        ])->scopeBindings();
    });

Route::middleware(['auth:sanctum', 'tenant.token'])->group(function () {
    Route::apiResource('sports', SportController::class);
    Route::apiResource('seasons', SeasonController::class);
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('coaches', CoachController::class);
});

// Documentación: Swagger UI y contrato OpenAPI. Fuera de local/staging (y de los tests) las
// rutas no se registran: producción responde exactamente como ante una ruta inexistente.
// ApiDocsAccess (config/scramble.php, "middleware") aplica la política por entorno como
// segunda capa. Excluidas del propio contrato por "api_path".
if (app()->environment(['local', 'staging', 'testing'])) {
    Scramble::registerUiRoute('docs')->name('api-docs.ui');
    Scramble::registerJsonSpecificationRoute('docs/openapi.json')->name('api-docs.json');
}
