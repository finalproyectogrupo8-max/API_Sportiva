<?php

namespace App\Jobs;

use App\Club\Access\ClubMembers;
use App\Club\Access\SyncClubPermissions;
use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Tenancy\TenantDatabase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProvisionTenant implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 60];

    public int $timeout = 60;

    public function __construct(public int $tenantId) {}

    public function handle(TenantDatabase $tenantDatabase, SyncClubPermissions $sync, ClubMembers $members): void
    {
        $tenant = Tenant::find($this->tenantId);

        // Si el club no existe o fue dado de baja (soft delete), el job termina sin error
        if (! $tenant || $tenant->trashed()) {
            return;
        }

        // Idempotente: si ya está activo, no repite trabajo
        if ($tenant->status === TenantStatus::Active->value) {
            return;
        }

        // En tests, solo se permiten bases con prefijo club_test_* para cumplir la convención de testing
        if (app()->environment('testing') && ! str_starts_with((string) $tenant->database_name, 'club_test_')) {
            return;
        }

        try {
            // 1. Crear base de datos de club (CREATE DATABASE IF NOT EXISTS)
            $tenantDatabase->create($tenant);

            // 2. Ejecutar las migraciones de database/migrations/tenant
            $tenantDatabase->migrate($tenant);

            // 3. Roles estándar y permisos; el admin inicial del alta recibe "Administrador del club"
            $tenantDatabase->run($tenant, function () use ($sync, $members, $tenant) {
                $sync->sync();
                $members->backfillAdmins($tenant);
            });

            // 4. Activar el club
            $tenant->update(['status' => TenantStatus::Active->value]);

            Log::info("Club [{$tenant->slug}] provisionado y activado exitosamente.", [
                'tenant_id' => $tenant->id,
                'slug' => $tenant->slug,
            ]);
        } catch (Throwable $e) {
            $this->markFailed($tenant, $e);

            // En testing con sync queue, evitamos relanzar la excepción para que el request HTTP
            // de creación no arroje 500 y refleje el estado 'failed' en la respuesta.
            if (! app()->runningUnitTests()) {
                throw $e;
            }
        }
    }

    public function failed(?Throwable $e): void
    {
        $tenant = Tenant::find($this->tenantId);

        if ($tenant) {
            $this->markFailed($tenant, $e);
        }
    }

    private function markFailed(Tenant $tenant, ?Throwable $e): void
    {
        $tenant->update(['status' => TenantStatus::Failed->value]);

        $message = $this->sanitizeMessage($e?->getMessage() ?? 'Error desconocido durante la provisión.');

        Log::error("Falló la provisión del club: {$message}", [
            'tenant_id' => $tenant->id,
            'slug' => $tenant->slug,
        ]);
    }

    private function sanitizeMessage(string $message): string
    {
        // Ocultar contraseñas, URLs o DSN con credenciales
        $sanitized = preg_replace('/password=[^; &]+/i', 'password=******', $message) ?? $message;
        $sanitized = preg_replace('/:[^@:]+@/i', ':******@', $sanitized) ?? $sanitized;

        return substr($sanitized, 0, 500);
    }
}
