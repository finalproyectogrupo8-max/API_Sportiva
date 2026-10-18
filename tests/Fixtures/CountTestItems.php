<?php

namespace Tests\Fixtures;

use App\Models\Tenant;
use App\Tenancy\TenantDatabase;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Job de club de ejemplo, solo para tests: es el patrón de docs/guia-jobs.md. Recibe el id del
 * club (nunca el modelo ni el nombre de la base) y trabaja dentro de TenantDatabase::run.
 */
class CountTestItems implements ShouldQueue
{
    use Queueable;

    /** @var array<int, int> Cantidad de test_items que vio cada job, por id de club. */
    public static array $counts = [];

    public function __construct(public int $tenantId) {}

    public function handle(TenantDatabase $tenantDatabase): void
    {
        $tenant = Tenant::findOrFail($this->tenantId);

        $tenantDatabase->run($tenant, function () use ($tenant) {
            self::$counts[$tenant->id] = TestItem::count();
        });
    }
}
