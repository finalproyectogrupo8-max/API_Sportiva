<?php

use App\Http\Controllers\HealthController;
use App\Http\Controllers\TenantController;
use Dedoc\Scramble\Scramble;
use Illuminate\Support\Facades\Route;

// Todas las rutas de este archivo quedan bajo el prefijo /api (ver bootstrap/app.php).

Route::get('/health', HealthController::class)->name('health');

Route::middleware(['auth:sanctum', 'platform.admin'])
    ->prefix('admin')
    ->group(function () {
        Route::apiResource('tenants', TenantController::class);
    });

// Documentación: Swagger UI y contrato OpenAPI. Fuera de local/staging (y de los tests) las
// rutas no se registran: producción responde exactamente como ante una ruta inexistente.
// ApiDocsAccess (config/scramble.php, "middleware") aplica la política por entorno como
// segunda capa. Excluidas del propio contrato por "api_path".
if (app()->environment(['local', 'staging', 'testing'])) {
    Scramble::registerUiRoute('docs')->name('api-docs.ui');
    Scramble::registerJsonSpecificationRoute('docs/openapi.json')->name('api-docs.json');
}
