<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Tenancy\TenantDatabase;
use Illuminate\Console\Command;
use Throwable;

class MigrateTenants extends Command
{
    protected $signature = 'tenants:migrate 
                            {--tenant= : Slug del club específico a migrar (opcional, por defecto migra todos los activos)} 
                            {--fresh : Ejecuta migrate:fresh en cada base} 
                            {--force : Fuerza la ejecución sin confirmación en producción}';

    protected $description = 'Aplica las migraciones de database/migrations/tenant en las bases de datos de los clubes activos';

    public function handle(TenantDatabase $tenantDatabase): int
    {
        $slug = $this->option('tenant');
        $fresh = (bool) $this->option('fresh');

        $query = Tenant::query()->where('status', 'active');
        if ($slug) {
            $query->where('slug', $slug);
        }

        $tenants = $query->orderBy('id')->get();

        if ($tenants->isEmpty()) {
            $this->info('No se encontraron clubes activos para migrar.');

            return self::SUCCESS;
        }

        $hasError = false;

        foreach ($tenants as $tenant) {
            $this->line("Migrando base de datos del club [{$tenant->name}] ({$tenant->database_name})...");

            try {
                $tenantDatabase->migrate($tenant, $fresh);
                $this->info("✔ Base [{$tenant->database_name}] migrada exitosamente.");
            } catch (Throwable $e) {
                $this->error("✖ Error al migrar base [{$tenant->database_name}]: {$e->getMessage()}");
                $hasError = true;
            }
        }

        return $hasError ? self::FAILURE : self::SUCCESS;
    }
}
