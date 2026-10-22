<?php

namespace App\Jobs\Platform;

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

    public function handle(TenantDatabase $tenantDatabase): void
    {
        $tenant = Tenant::findOrFail($this->tenantId);

        if ($tenant->status === TenantStatus::Active->value) {
            Log::info("El club [{$tenant->slug}] ya se encuentra activo. Provisión omitida.", [
                'tenant_id' => $tenant->id,
            ]);

            return;
        }

        // En tests que no usan prefijo de pruebas club_test_*, no creamos la base en MySQL real
        if (app()->environment('testing') && ! str_starts_with((string) $tenant->database_name, 'club_test_')) {
            $tenant->update(['status' => TenantStatus::Active->value]);

            return;
        }

        // 1. Crear base de datos de club (idempotente: CREATE DATABASE IF NOT EXISTS)
        $tenantDatabase->create($tenant);

        // 2. Ejecutar las migraciones de tenant/
        $tenantDatabase->migrate($tenant);

        // 3. Activar el club
        $tenant->update(['status' => TenantStatus::Active->value]);

        Log::info("Club [{$tenant->slug}] provisionado y activado exitosamente.", [
            'tenant_id' => $tenant->id,
        ]);
    }

    public function failed(?Throwable $e): void
    {
        Tenant::whereKey($this->tenantId)->update(['status' => TenantStatus::Failed->value]);

        Log::error('Falló la provisión del club', [
            'tenant_id' => $this->tenantId,
            'error' => $e?->getMessage(),
        ]);
    }
}
