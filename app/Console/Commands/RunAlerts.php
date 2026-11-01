<?php

namespace App\Console\Commands;

use App\Club\Alerts\AlertRunner;
use App\Models\Tenant;
use App\Tenancy\TenantDatabase;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class RunAlerts extends Command
{
    protected $signature = 'alerts:run
                            {--club= : Slug de un club específico}
                            {--dry-run : Ejecuta las reglas sin persistir notificaciones ni marcas de alerta}';

    protected $description = 'Ejecuta las alertas automáticas del recorrido del partido en los clubes activos';

    public function handle(TenantDatabase $tenantDatabase, AlertRunner $runner): int
    {
        $clubSlug = $this->option('club');
        $dryRun = (bool) $this->option('dry-run');

        $tenants = Tenant::query()
            ->where('status', 'active')
            ->when($clubSlug, fn ($query) => $query->where('slug', $clubSlug))
            ->orderBy('id')
            ->get();

        if ($tenants->isEmpty()) {
            $this->info('No se encontraron clubes activos.');

            return self::SUCCESS;
        }

        $failedClubs = [];
        $totalAlerts = 0;

        foreach ($tenants as $tenant) {
            try {
                $count = $tenantDatabase->run($tenant, fn () => $runner->run($tenant, $dryRun));
                $totalAlerts += $count;
                $this->info("✔ Club [{$tenant->slug}]: {$count} alerta(s) generada(s)".($dryRun ? ' (dry-run)' : '').'.');
            } catch (Throwable $e) {
                Log::error("Error ejecutando alertas en club [{$tenant->slug}]: {$e->getMessage()}", [
                    'tenant' => $tenant->slug,
                    'exception' => $e,
                ]);

                $this->error("✖ Error en club [{$tenant->slug}]: {$e->getMessage()}");
                $failedClubs[] = $tenant->slug;
            }
        }

        if ($failedClubs !== []) {
            $this->error('Fallaron los siguientes clubes: '.implode(', ', $failedClubs).'.');

            return self::FAILURE;
        }

        $this->info("Proceso de alertas finalizado. Total de alertas: {$totalAlerts}.");

        return self::SUCCESS;
    }
}
