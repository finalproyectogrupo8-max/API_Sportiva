<?php

use App\Http\Controllers\Admin\SupportTicketController as AdminSupportTicketController;
use App\Http\Controllers\Admin\TenantController;
use App\Http\Controllers\Admin\TenantMembershipController;
use App\Http\Controllers\Admin\TenantRoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\UserMembershipController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Club\ApparelItemController;
use App\Http\Controllers\Club\AttendanceController;
use App\Http\Controllers\Club\CalendarController;
use App\Http\Controllers\Club\CallupController;
use App\Http\Controllers\Club\CategoryCoachController;
use App\Http\Controllers\Club\CategoryController;
use App\Http\Controllers\Club\CategoryMembershipController;
use App\Http\Controllers\Club\ClubController;
use App\Http\Controllers\Club\ClubLogoController;
use App\Http\Controllers\Club\CoachController;
use App\Http\Controllers\Club\CompetitionController;
use App\Http\Controllers\Club\DocumentController;
use App\Http\Controllers\Club\EligibilityController;
use App\Http\Controllers\Club\FacilityComponentController;
use App\Http\Controllers\Club\FacilityController;
use App\Http\Controllers\Club\InventoryBalanceController;
use App\Http\Controllers\Club\InventoryMovementController;
use App\Http\Controllers\Club\InventoryTransferController;
use App\Http\Controllers\Club\MaintenanceRecordController;
use App\Http\Controllers\Club\MatchController;
use App\Http\Controllers\Club\MaterialController;
use App\Http\Controllers\Club\OrderController;
use App\Http\Controllers\Club\PlayerContactController;
use App\Http\Controllers\Club\PlayerController;
use App\Http\Controllers\Club\RepairRequestController;
use App\Http\Controllers\Club\RoleController;
use App\Http\Controllers\Club\SeasonController;
use App\Http\Controllers\Club\SportController;
use App\Http\Controllers\Club\StorageLocationController;
use App\Http\Controllers\Club\TrainingController;
use App\Http\Controllers\Club\VenueController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\PublicCallupController;
use App\Http\Controllers\SupportTicketController;
use Dedoc\Scramble\Scramble;
use Illuminate\Support\Facades\Route;

// Todas las rutas de este archivo quedan bajo el prefijo /api (ver bootstrap/app.php).

Route::get('/health', HealthController::class)->name('health');
Route::post('tickets', [SupportTicketController::class, 'store'])->middleware('throttle:tickets');
Route::post('/newsletter', [NewsletterController::class, 'store']);

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
        Route::get('tenants/{tenant}/permissions', [TenantRoleController::class, 'permissions']);
        Route::get('tenants/{tenant}/roles', [TenantRoleController::class, 'index']);
        Route::post('tenants/{tenant}/roles', [TenantRoleController::class, 'store']);
        Route::get('tenants/{tenant}/roles/{role}', [TenantRoleController::class, 'show']);
        Route::patch('tenants/{tenant}/roles/{role}', [TenantRoleController::class, 'update']);
        Route::delete('tenants/{tenant}/roles/{role}', [TenantRoleController::class, 'destroy']);
        Route::post('tenants/{tenant}/roles/{role}/duplicate', [TenantRoleController::class, 'duplicate']);
        Route::get('tenants/{tenant}/memberships', [TenantMembershipController::class, 'index']);
        Route::get('users/{user}/memberships', [UserMembershipController::class, 'index']);
        Route::post('tenants/{tenant}/memberships', [TenantMembershipController::class, 'store']);
        Route::patch('tenants/{tenant}/memberships/{membership}', [
            TenantMembershipController::class,
            'update',
        ])->scopeBindings();
        Route::apiResource('tickets', AdminSupportTicketController::class)->only(['index', 'show', 'update']);
    });

// Convocatoria (TIZ-33): lo que abren el jugador y su familia desde el link, sin login.
Route::middleware('throttle:60,1')->prefix('public/callups')->group(function () {
    Route::get('respond/{token}', [PublicCallupController::class, 'showPersonal']);
    Route::post('respond/{token}', [PublicCallupController::class, 'respond']);
    Route::get('{token}/calendar.ics', [PublicCallupController::class, 'calendar']);
    Route::get('{token}', [PublicCallupController::class, 'show']);
});

// Escudo de un club activo: se muestra antes de entrar (selector, panel). No es un dato sensible.
Route::get('public/clubs/{slug}/logo', [ClubLogoController::class, 'show'])->middleware('throttle:120,1');

Route::middleware(['auth:sanctum', 'tenant.token'])->group(function () {
    Route::get('club', [ClubController::class, 'show']);
    Route::patch('club', [ClubController::class, 'update']);
    Route::apiResource('sports', SportController::class);
    Route::apiResource('seasons', SeasonController::class);
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('coaches', CoachController::class);
    Route::apiResource('players', PlayerController::class);
    Route::get('players/{player}/contacts', [PlayerContactController::class, 'index']);
    Route::post('players/{player}/contacts', [PlayerContactController::class, 'store']);
    Route::patch('player-contacts/{contact}', [PlayerContactController::class, 'update']);
    Route::delete('player-contacts/{contact}', [PlayerContactController::class, 'destroy']);
    Route::get('players/{player}/memberships', [CategoryMembershipController::class, 'forPlayer']);
    Route::get('categories/{category}/memberships', [CategoryMembershipController::class, 'index']);
    Route::post('categories/{category}/memberships', [CategoryMembershipController::class, 'store']);
    Route::patch('memberships/{membership}', [CategoryMembershipController::class, 'update']);
    Route::post('memberships/{membership}/end', [CategoryMembershipController::class, 'end']);
    Route::get('categories/{category}/coaches', [CategoryCoachController::class, 'index']);
    Route::post('categories/{category}/coaches', [CategoryCoachController::class, 'store']);
    Route::post('category-coaches/{categoryCoach}/end', [CategoryCoachController::class, 'end']);
    Route::apiResource('matches', MatchController::class)->parameters(['matches' => 'match']);
    Route::apiResource('trainings', TrainingController::class);
    Route::get('matches/{match}/attendance', [AttendanceController::class, 'showMatch']);
    Route::put('matches/{match}/attendance', [AttendanceController::class, 'recordMatch']);
    Route::get('trainings/{training}/attendance', [AttendanceController::class, 'showTraining']);
    Route::put('trainings/{training}/attendance', [AttendanceController::class, 'recordTraining']);
    Route::get('calendar', CalendarController::class);
    Route::apiResource('competitions', CompetitionController::class);
    Route::apiResource('venues', VenueController::class);
    Route::post('competitions/{competition}/copy', [CompetitionController::class, 'copy']);
    Route::get('players/{player}/documents', [DocumentController::class, 'index']);
    Route::post('players/{player}/documents', [DocumentController::class, 'store']);
    Route::get('documents/{document}/download', [DocumentController::class, 'download']);
    Route::delete('documents/{document}', [DocumentController::class, 'destroy']);
    Route::get('eligibility', [EligibilityController::class, 'index']);
    Route::get('categories/{category}/eligibility', [EligibilityController::class, 'category']);
    Route::get('players/{player}/eligibility', [EligibilityController::class, 'player']);
    Route::post('players/{player}/requirements/{requirement}/submit', [EligibilityController::class, 'submit']);
    Route::post('players/{player}/requirements/{requirement}/approve', [EligibilityController::class, 'approve']);
    Route::post('players/{player}/requirements/{requirement}/reject', [EligibilityController::class, 'reject']);
    Route::get('matches/{match}/callup', [CallupController::class, 'show']);
    Route::put('matches/{match}/callup', [CallupController::class, 'update']);
    Route::post('matches/{match}/callup/publish', [CallupController::class, 'publish']);
    Route::post('club/logo', [ClubLogoController::class, 'store']);
    Route::delete('club/logo', [ClubLogoController::class, 'destroy']);
    Route::get('permissions', [RoleController::class, 'permissions']);
    Route::apiResource('roles', RoleController::class)->parameters(['roles' => 'role:slug']);
    Route::post('roles/{role:slug}/duplicate', [RoleController::class, 'duplicate']);
    Route::apiResource('storage-locations', StorageLocationController::class);
    Route::apiResource('materials', MaterialController::class);
    Route::get('inventory/balances', [InventoryBalanceController::class, 'index']);
    Route::get('inventory/movements', [InventoryMovementController::class, 'index']);
    Route::post('inventory/movements', [InventoryMovementController::class, 'store']);
    Route::post('inventory/transfers', [InventoryTransferController::class, 'store']);
    Route::apiResource('facilities', FacilityController::class);
    Route::apiResource('facilities.components', FacilityComponentController::class)->shallow();
    Route::apiResource('repair-requests', RepairRequestController::class);
    Route::post('repair-requests/{repair_request}/assign', [RepairRequestController::class, 'assign']);
    Route::post('repair-requests/{repair_request}/resolve', [RepairRequestController::class, 'resolve']);
    Route::apiResource('maintenance-records', MaintenanceRecordController::class);
    Route::apiResource('apparel-items', ApparelItemController::class);
    Route::apiResource('orders', OrderController::class);
    Route::post('orders/{order}/submit', [OrderController::class, 'submit']);
    Route::post('orders/{order}/approve', [OrderController::class, 'approve']);
    Route::post('orders/{order}/reject', [OrderController::class, 'reject']);
    Route::post('orders/{order}/deliveries', [OrderController::class, 'deliveries']);
});

// Documentación: Swagger UI y contrato OpenAPI. Fuera de local/staging (y de los tests) las
// rutas no se registran: producción responde exactamente como ante una ruta inexistente.
// ApiDocsAccess (config/scramble.php, "middleware") aplica la política por entorno como
// segunda capa. Excluidas del propio contrato por "api_path".
if (app()->environment(['local', 'staging', 'testing'])) {
    Scramble::registerUiRoute('docs')->name('api-docs.ui');
    Scramble::registerJsonSpecificationRoute('docs/openapi.json')->name('api-docs.json');
}
