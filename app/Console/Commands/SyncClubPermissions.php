<?php

namespace App\Console\Commands;

use App\Club\Access\ClubMembers;
use App\Club\Access\SyncClubPermissions as SyncCatalog;
use App\Models\Tenant;
use App\Tenancy\TenantDatabase;
use Illuminate\Console\Command;
use Throwable;

class SyncClubPermissions extends Command
{
    protected $signature = 'club:sync-permissions
                            {--tenant= : Slug del club (opcional, por defecto todos los activos)}';

    protected $description = 'Copia el catálogo de permisos y los roles estándar (config/club-permissions.php) a las bases de los clubes';

    public function handle(TenantDatabase $tenantDatabase, SyncCatalog $sync, ClubMembers $members): int
    {
        $tenants = Tenant::query()
            ->where('status', 'active')
            ->when($this->option('tenant'), fn ($query, $slug) => $query->where('slug', $slug))
            ->orderBy('id')
            ->get();

        if ($tenants->isEmpty()) {
            $this->info('No se encontraron clubes activos.');

            return self::SUCCESS;
        }

        $hasError = false;

        foreach ($tenants as $tenant) {
            try {
                $created = $tenantDatabase->run($tenant, function () use ($sync, $members, $tenant) {
                    $sync->sync();

                    return $members->backfillAdmins($tenant);
                });

                $this->info("✔ Permisos de [{$tenant->database_name}] sincronizados".($created ? " ({$created} administradores creados)" : '').'.');
            } catch (Throwable $e) {
                $this->error("✖ Error en [{$tenant->database_name}]: {$e->getMessage()}");
                $hasError = true;
            }
        }

        return $hasError ? self::FAILURE : self::SUCCESS;
    }
}
