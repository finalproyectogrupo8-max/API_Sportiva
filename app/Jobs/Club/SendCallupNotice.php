<?php

namespace App\Jobs\Club;

use App\Club\Callups\CallupMessages;
use App\Club\Callups\CallupNoticeMode;
use App\Mail\CallupNoticeMail;
use App\Models\Club\GameMatch;
use App\Models\Club\MatchCallup;
use App\Models\Tenant;
use App\Tenancy\TenantDatabase;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Manda UN mail del aviso de la convocatoria (TIZ-37) a una dirección, con el link personal de cada
 * convocado de esa dirección (un tutor con dos hijos recibe uno solo). Recibe IDs y no datos
 * personales: la dirección se lee de la base del club cuando corre.
 *
 * Idempotente: descarta los convocados que ya recibieron este aviso (`notified_at >= requestedAt`).
 */
class SendCallupNotice implements ShouldQueue
{
    use Queueable;

    // El límite de envío (RateLimited) devuelve el job a la cola y eso cuenta como intento: con
    // `tries` fijo, en un envío grande los jobs se daban por fallidos sin haber mandado nada.
    // Se reintenta hasta una hora y se corta a los 3 errores reales.
    public int $maxExceptions = 3;

    public array $backoff = [10, 60];

    public int $timeout = 30;

    public function retryUntil(): DateTimeInterface
    {
        return now()->addHour();
    }

    /** @param  list<int>  $callupIds */
    public function __construct(
        public int $tenantId,
        public int $matchId,
        public array $callupIds,
        public CallupNoticeMode $mode,
        public string $requestedAt,
    ) {}

    /** @return list<object> */
    public function middleware(): array
    {
        // Con la cola sync (tests), un job limitado se "libera" y no vuelve a correr: el mail se perdería.
        return config('queue.default') === 'sync' ? [] : [new RateLimited('mail')];
    }

    public function handle(TenantDatabase $tenantDatabase, CallupMessages $messages): void
    {
        $tenant = Tenant::find($this->tenantId);
        if (! $tenant) {
            return;
        }

        $tenantDatabase->run($tenant, function () use ($tenant, $messages) {
            $match = GameMatch::with('category')->find($this->matchId);
            if (! $match || $match->callup_published_at === null) {
                return;
            }

            $requestedAt = CarbonImmutable::parse($this->requestedAt);
            $callups = MatchCallup::query()
                ->where('match_id', $match->id)
                ->whereKey($this->callupIds)
                ->with('player.contacts')
                ->get()
                ->reject(fn (MatchCallup $c) => $c->notified_at !== null && $c->notified_at->gte($requestedAt))
                ->when($this->mode === CallupNoticeMode::Reminder, fn (Collection $c) => $c->where('response', MatchCallup::PENDING));

            // La dirección se vuelve a leer: si cambió desde que se repartió, sale a la nueva.
            $byEmail = $callups->groupBy(fn (MatchCallup $c) => NotifyCallup::emailOf($c))->forget('');

            foreach ($byEmail as $email => $group) {
                Mail::to($email)->sendNow(new CallupNoticeMail(
                    $messages->mailSubject($match, $tenant, $this->mode),
                    $this->mode,
                    $tenant->short_name ?: $tenant->name,
                    $match->category->name,
                    explode("\n", $messages->summary($match)),
                    $group->map(fn (MatchCallup $c) => ['name' => $c->player->first_name, 'url' => $messages->personalUrl($c)])->values()->all(),
                ));

                // Se marca con el momento del pedido, no del envío: un reintento de este aviso queda
                // igual y se descarta, y uno pedido después es mayor y sale. Los dos tienen precisión
                // de segundos, así que dos pedidos en el mismo segundo cuentan como el mismo aviso.
                MatchCallup::whereKey($group->modelKeys())->update(['notified_at' => $requestedAt]);
            }
        });
    }

    public function failed(?Throwable $e): void
    {
        // Sin el mensaje de la excepción: el proveedor suele incluir la dirección.
        Log::error('No se pudo mandar el aviso de la convocatoria por mail.', [
            'tenant_id' => $this->tenantId,
            'match_id' => $this->matchId,
            'callup_ids' => $this->callupIds,
            'error' => $e ? $e::class : null,
        ]);
    }
}
