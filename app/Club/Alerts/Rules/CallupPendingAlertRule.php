<?php

namespace App\Club\Alerts\Rules;

use App\Club\Alerts\AlertRecipients;
use App\Club\Alerts\Channels\NotificationChannel;
use App\Models\Club\AlertRecord;
use App\Models\Club\GameMatch;

/**
 * Alerta 4: Convocatoria.
 * Sin publicar a 48 horas del partido.
 * Avisa al profe de la categoría y al coordinador.
 */
class CallupPendingAlertRule
{
    public function __construct(
        private AlertRecipients $recipients,
        private NotificationChannel $channel,
    ) {}

    public function evaluate(bool $dryRun = false): int
    {
        $thresholdHours = (int) config('alerts.thresholds.callup_pending_hours', 48);
        $now = now();
        $targetDateTime = $now->copy()->addHours($thresholdHours);

        $pendingMatches = GameMatch::query()
            ->where('status', 'scheduled')
            ->where('match_date', '>=', $now)
            ->where('match_date', '<=', $targetDateTime)
            ->with('category')
            ->get();

        $alertsGenerated = 0;

        foreach ($pendingMatches as $match) {
            $dedupKey = "callup_pending:{$match->id}";

            // Si ya tiene convocatoria publicada, se marca como resuelto
            if ($match->callup_published_at !== null) {
                if (! $dryRun) {
                    AlertRecord::query()
                        ->where('dedup_key', $dedupKey)
                        ->whereNull('resolved_at')
                        ->update(['resolved_at' => now(), 'updated_at' => now()]);
                }

                continue;
            }

            $alreadyNotified = AlertRecord::query()
                ->where('dedup_key', $dedupKey)
                ->whereNull('resolved_at')
                ->exists();

            if ($alreadyNotified) {
                continue;
            }

            $alertsGenerated++;

            if ($dryRun) {
                continue;
            }

            $category = $match->category;
            $recipientIds = $category ? $this->recipients->forCategory($category) : $this->recipients->generalCoordinators();

            $hoursLeft = (int) $now->diffInHours($match->match_date, false);
            $hoursText = $hoursLeft <= 1 ? 'en menos de una hora' : "en {$hoursLeft} horas ({$match->match_date->format('d/m/Y H:i')})";

            $categoryName = $category?->name ?? 'Categoría';
            $title = "Convocatoria pendiente: vs {$match->opponent}";
            $body = "El partido contra {$match->opponent} ({$categoryName}) es {$hoursText} y la convocatoria aún no fue publicada.";

            $data = [
                'match_id' => $match->id,
                'category_id' => $match->category_id,
                'match_date' => $match->match_date->toIso8601String(),
                'hours_left' => $hoursLeft,
            ];

            $this->channel->send(
                $recipientIds,
                'callup_pending',
                $title,
                $body,
                $data,
            );

            AlertRecord::query()->create([
                'alert_type' => 'callup_pending',
                'subject_type' => 'match',
                'subject_id' => $match->id,
                'dedup_key' => $dedupKey,
                'notified_at' => now(),
            ]);
        }

        return $alertsGenerated;
    }
}
