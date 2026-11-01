<?php

namespace App\Console\Commands;

use App\Models\Club\PlayerRequirement;
use App\Models\Tenant;
use App\Tenancy\TenantDatabase;
use Illuminate\Console\Command;

/**
 * Pasa a `expired` los requisitos aprobados que pasaron su vencimiento, en cada club activo (TIZ-32).
 * Corre todos los días. La API ya los muestra vencidos aunque no corra (effectiveStatus); esto deja
 * el estado guardado al día para filtros y alertas.
 */
class ExpireRequirements extends Command
{
    protected $signature = 'club:expire-requirements {--tenant= : Slug de un solo club}';

    protected $description = 'Marca como vencidos los requisitos de habilitación aprobados que pasaron su fecha';

    public function handle(TenantDatabase $tenantDatabase): int
    {
        $tenants = Tenant::query()
            ->where('status', 'active')
            ->when($this->option('tenant'), fn ($query, $slug) => $query->where('slug', $slug))
            ->get();

        foreach ($tenants as $tenant) {
            $count = $tenantDatabase->run($tenant, fn () => PlayerRequirement::query()
                ->where('status', PlayerRequirement::APPROVED)
                ->whereNotNull('expires_at')
                ->where('expires_at', '<', today()->toDateString())
                ->update(['status' => PlayerRequirement::EXPIRED, 'updated_at' => now()]));

            $this->info("[{$tenant->slug}] {$count} requisito(s) vencido(s).");
        }

        return self::SUCCESS;
    }
}
