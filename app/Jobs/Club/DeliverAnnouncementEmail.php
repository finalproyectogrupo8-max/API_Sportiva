<?php

namespace App\Jobs\Club;

use App\Mail\AnnouncementMail;
use App\Models\Club\Announcement;
use App\Models\Club\AnnouncementRecipient;
use App\Models\Club\ClubUser;
use App\Models\Tenant;
use App\Models\User;
use App\Tenancy\TenantDatabase;
use DateTimeInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

/**
 * Manda el mail de un comunicado a UN destinatario (TIZ-24). Recibe IDs: la dirección se lee de
 * `announcement_recipients` cuando corre. Idempotente: si la fila ya no está en `pending`, termina.
 */
class DeliverAnnouncementEmail implements ShouldQueue
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

    public function __construct(public int $tenantId, public int $recipientId) {}

    /** @return list<object> */
    public function middleware(): array
    {
        // Con la cola sync (tests), un job limitado se "libera" y no vuelve a correr: el mail se perdería.
        return config('queue.default') === 'sync' ? [] : [new RateLimited('mail')];
    }

    public function handle(TenantDatabase $tenantDatabase): void
    {
        $tenant = Tenant::find($this->tenantId);
        if (! $tenant) {
            return;
        }

        $tenantDatabase->run($tenant, function () use ($tenant) {
            $recipient = AnnouncementRecipient::with('announcement')->find($this->recipientId);
            if (! $recipient || $recipient->status !== AnnouncementRecipient::PENDING || ! $recipient->announcement) {
                return;
            }

            $announcement = $recipient->announcement;
            Mail::to($recipient->email)->sendNow(new AnnouncementMail(
                $tenant->short_name ?: $tenant->name,
                $announcement->subject,
                $announcement->body,
                $recipient->name,
                self::senderEmail($announcement),
            ));

            $recipient->update(['status' => AnnouncementRecipient::SENT, 'sent_at' => now(), 'error' => null]);
            self::closeIfDone($announcement->id);
        });
    }

    public function failed(?Throwable $e): void
    {
        $tenant = Tenant::find($this->tenantId);
        if ($tenant) {
            app(TenantDatabase::class)->run($tenant, function () use ($e) {
                $recipient = AnnouncementRecipient::find($this->recipientId);
                if ($recipient && $recipient->status === AnnouncementRecipient::PENDING) {
                    $recipient->update(['status' => AnnouncementRecipient::FAILED, 'error' => Str::limit((string) $e?->getMessage(), 250)]);
                    self::closeIfDone($recipient->announcement_id);
                }
            });
        }

        // Sin el mensaje de la excepción: el proveedor suele incluir la dirección.
        Log::error('No se pudo mandar un comunicado por mail.', [
            'tenant_id' => $this->tenantId,
            'recipient_id' => $this->recipientId,
            'error' => $e ? $e::class : null,
        ]);
    }

    /** Actualiza los contadores y, si no quedan pendientes, cierra el comunicado en `sent` (una sola vez). */
    public static function closeIfDone(int $announcementId): void
    {
        $counts = AnnouncementRecipient::query()->where('announcement_id', $announcementId)
            ->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        Announcement::whereKey($announcementId)->update([
            'sent_count' => (int) ($counts[AnnouncementRecipient::SENT] ?? 0),
            'failed_count' => (int) ($counts[AnnouncementRecipient::FAILED] ?? 0),
        ]);

        if ((int) ($counts[AnnouncementRecipient::PENDING] ?? 0) === 0) {
            Announcement::whereKey($announcementId)->where('status', Announcement::SENDING)->update(['status' => Announcement::SENT]);
        }
    }

    /** Email de quien lo envió (base central), para el Reply-To. */
    private static function senderEmail(Announcement $announcement): ?string
    {
        $globalUserId = ClubUser::whereKey($announcement->sent_by)->value('global_user_id');

        return $globalUserId ? User::whereKey($globalUserId)->value('email') : null;
    }
}
