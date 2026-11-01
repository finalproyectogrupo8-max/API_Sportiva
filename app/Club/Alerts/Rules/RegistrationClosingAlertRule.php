<?php

namespace App\Club\Alerts\Rules;

use App\Club\Alerts\AlertRecipients;
use App\Club\Alerts\Channels\NotificationChannel;
use App\Club\Eligibility\EligibilityCalculator;
use App\Models\Club\AlertRecord;
use App\Models\Club\Competition;

/**
 * Alerta 3: Cierre de inscripción.
 * A 7 días del cierre, con jugadores sin habilitar en la categoría.
 * Avisa al profe de la categoría y al coordinador.
 */
class RegistrationClosingAlertRule
{
    public function __construct(
        private AlertRecipients $recipients,
        private NotificationChannel $channel,
        private EligibilityCalculator $calculator,
    ) {}

    public function evaluate(bool $dryRun = false): int
    {
        $thresholdDays = (int) config('alerts.thresholds.registration_closing_days', 7);
        $today = today();
        $targetDate = $today->copy()->addDays($thresholdDays);

        $closingCompetitions = Competition::query()
            ->whereNotNull('registration_deadline')
            ->where('registration_deadline', '>=', $today->toDateString())
            ->where('registration_deadline', '<=', $targetDate->toDateString())
            ->with('categories')
            ->get();

        $alertsGenerated = 0;

        foreach ($closingCompetitions as $competition) {
            foreach ($competition->categories as $category) {
                $players = $this->calculator->forCategory($category);
                $ineligiblePlayers = array_filter($players, fn ($p) => ! $p->is_eligible);
                $ineligibleCount = count($ineligiblePlayers);

                $dedupKey = "registration_closing:{$competition->id}:{$category->id}:{$competition->registration_deadline->toDateString()}";

                if ($ineligibleCount === 0) {
                    // Si ya no hay jugadores sin habilitar, se resuelve la alerta activa
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

                $recipientIds = $this->recipients->forCategory($category);

                $daysLeft = (int) $today->diffInDays($competition->registration_deadline, false);
                $daysText = $daysLeft === 0 ? 'hoy' : ($daysLeft === 1 ? 'mañana' : "en {$daysLeft} días ({$competition->registration_deadline->format('d/m/Y')})");

                $title = "Cierre de inscripción: {$competition->name}";
                $body = "La inscripción para {$competition->name} cierra {$daysText}. La categoría {$category->name} tiene {$ineligibleCount} jugador(es) sin habilitar.";

                $data = [
                    'competition_id' => $competition->id,
                    'category_id' => $category->id,
                    'ineligible_count' => $ineligibleCount,
                    'deadline' => $competition->registration_deadline->toDateString(),
                    'days_left' => $daysLeft,
                ];

                $this->channel->send(
                    $recipientIds,
                    'registration_closing',
                    $title,
                    $body,
                    $data,
                );

                AlertRecord::query()->create([
                    'alert_type' => 'registration_closing',
                    'subject_type' => 'competition',
                    'subject_id' => $competition->id,
                    'dedup_key' => $dedupKey,
                    'notified_at' => now(),
                ]);
            }
        }

        return $alertsGenerated;
    }
}
