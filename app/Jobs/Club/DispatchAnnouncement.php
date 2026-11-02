<?php

namespace App\Jobs\Club;

use App\Models\Club\AnnouncementRecipient;
use App\Models\Tenant;
use App\Tenancy\TenantDatabase;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Reparte el envío de un comunicado (TIZ-24): un DeliverAnnouncementEmail por destinatario pendiente.
 * Solo reparte, así que es idempotente: cada envío mira el estado de su fila.
 */
class DispatchAnnouncement implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [10, 60];

    public int $timeout = 30;

    public function __construct(public int $tenantId, public int $announcementId) {}

    public function handle(TenantDatabase $tenantDatabase): void
    {
        $tenant = Tenant::find($this->tenantId);
        if (! $tenant) {
            return;
        }

        $tenantDatabase->run($tenant, function () {
            AnnouncementRecipient::query()
                ->where('announcement_id', $this->announcementId)
                ->where('status', AnnouncementRecipient::PENDING)
                ->pluck('id')
                ->each(fn (int $id) => DeliverAnnouncementEmail::dispatch($this->tenantId, $id));

            DeliverAnnouncementEmail::closeIfDone($this->announcementId);
        });
    }
}
