<?php

namespace App\Jobs\Club;

use App\Club\Callups\CallupNoticeMode;
use App\Models\Club\GameMatch;
use App\Models\Club\MatchCallup;
use App\Models\Tenant;
use App\Tenancy\TenantDatabase;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Reparte el aviso por mail de la convocatoria (TIZ-37): elige a qué convocados avisar según el modo,
 * los agrupa por dirección y despacha un SendCallupNotice por dirección. Solo reparte, así que es
 * idempotente: los envíos repetidos se descartan en SendCallupNotice.
 */
class NotifyCallup implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [10, 60];

    public int $timeout = 30;

    public function __construct(
        public int $tenantId,
        public int $matchId,
        public CallupNoticeMode $mode,
        public string $requestedAt,
    ) {}

    /** Email del contacto principal, normalizado; vacío si no tiene. */
    public static function emailOf(MatchCallup $callup): string
    {
        $email = $callup->player->contacts->firstWhere('is_primary', true)?->email;

        return mb_strtolower(trim((string) $email));
    }

    public function handle(TenantDatabase $tenantDatabase): void
    {
        $tenant = Tenant::find($this->tenantId);
        if (! $tenant) {
            return;
        }

        $tenantDatabase->run($tenant, function () {
            $match = GameMatch::find($this->matchId);
            if (! $match || $match->callup_published_at === null) {
                return;
            }

            $callups = MatchCallup::query()
                ->where('match_id', $match->id)
                ->when($this->mode === CallupNoticeMode::Published, fn ($q) => $q->whereNull('notified_at'))
                ->when($this->mode === CallupNoticeMode::Reminder, fn ($q) => $q->where('response', MatchCallup::PENDING))
                ->with('player.contacts')
                ->get();

            $callups->groupBy(fn (MatchCallup $c) => self::emailOf($c))
                ->forget('')
                ->each(fn ($group) => SendCallupNotice::dispatch(
                    $this->tenantId, $this->matchId, $group->modelKeys(), $this->mode, $this->requestedAt,
                ));
        });
    }
}
