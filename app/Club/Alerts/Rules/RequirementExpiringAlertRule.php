<?php

namespace App\Club\Alerts\Rules;

use App\Club\Alerts\AlertRecipients;
use App\Club\Alerts\Channels\NotificationChannel;
use App\Models\Club\AlertRecord;
use App\Models\Club\PlayerRequirement;

/**
 * Alerta 2: Requisito por vencer.
 * A 30 días del vencimiento (requisitos por jugador, TIZ-32).
 * Avisa al profe de la categoría y al coordinador.
 */
class RequirementExpiringAlertRule
{
    public function __construct(
        private AlertRecipients $recipients,
        private NotificationChannel $channel,
    ) {}

    public function evaluate(bool $dryRun = false): int
    {
        $thresholdDays = (int) config('alerts.thresholds.requirement_expiring_days', 30);
        $today = today();
        $targetDate = $today->copy()->addDays($thresholdDays);

        $expiringRequirements = PlayerRequirement::query()
            ->where('status', PlayerRequirement::APPROVED)
            ->whereNotNull('expires_at')
            ->where('expires_at', '>=', $today->toDateString())
            ->where('expires_at', '<=', $targetDate->toDateString())
            ->with(['requirement.competition', 'player.activeMemberships.category'])
            ->get();

        $alertsGenerated = 0;

        foreach ($expiringRequirements as $playerReq) {
            $player = $playerReq->player;
            $requirement = $playerReq->requirement;

            if (! $player || ! $requirement) {
                continue;
            }

            $dedupKey = "requirement_expiring:{$playerReq->id}:{$playerReq->expires_at->toDateString()}";

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

            // Destinatarios: profes de las categorías activas del jugador + coordinadores
            $recipientIds = [];
            $activeCategories = $player->activeMemberships->map->category->filter();

            if ($activeCategories->isNotEmpty()) {
                foreach ($activeCategories as $category) {
                    $recipientIds = array_merge($recipientIds, $this->recipients->forCategory($category));
                }
            } else {
                $recipientIds = $this->recipients->generalCoordinators();
            }

            $recipientIds = array_values(array_unique($recipientIds));

            $title = "Requisito por vencer: {$requirement->name}";
            $daysLeft = (int) $today->diffInDays($playerReq->expires_at, false);
            $daysText = $daysLeft === 0 ? 'hoy' : ($daysLeft === 1 ? 'mañana' : "en {$daysLeft} días ({$playerReq->expires_at->format('d/m/Y')})");
            $body = "El requisito {$requirement->name} de {$player->first_name} {$player->last_name} vence {$daysText}.";

            $data = [
                'player_id' => $player->id,
                'player_requirement_id' => $playerReq->id,
                'competition_requirement_id' => $playerReq->competition_requirement_id,
                'expires_at' => $playerReq->expires_at->toDateString(),
                'days_left' => $daysLeft,
            ];

            $this->channel->send(
                $recipientIds,
                'requirement_expiring',
                $title,
                $body,
                $data,
            );

            AlertRecord::query()->create([
                'alert_type' => 'requirement_expiring',
                'subject_type' => 'player_requirement',
                'subject_id' => $playerReq->id,
                'dedup_key' => $dedupKey,
                'notified_at' => now(),
            ]);
        }

        return $alertsGenerated;
    }
}
