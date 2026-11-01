<?php

namespace App\Club\Alerts\Rules;

use App\Club\Alerts\AlertRecipients;
use App\Club\Alerts\Channels\NotificationChannel;
use App\Models\Club\AlertRecord;
use App\Models\Club\Category;
use App\Models\Club\PlayerCategoryMembership;
use Illuminate\Support\Facades\DB;

/**
 * Alerta 1: Abandono.
 * 3 ausencias seguidas sin justificar, o asistencia del último mes menor al 50 % cuando su promedio era mayor al 75 %.
 * Avisa al profe de la categoría y al coordinador.
 */
class AttendanceAlertRule
{
    public function __construct(
        private AlertRecipients $recipients,
        private NotificationChannel $channel,
    ) {}

    public function evaluate(bool $dryRun = false): int
    {
        $consecutiveThreshold = (int) config('alerts.thresholds.consecutive_absences', 3);
        $currentMax = (float) config('alerts.thresholds.attendance_drop_current_max', 50.0);
        $priorMin = (float) config('alerts.thresholds.attendance_drop_prior_min', 75.0);

        $categories = Category::query()->where('is_active', true)->get();
        $alertsGenerated = 0;

        foreach ($categories as $category) {
            $memberships = PlayerCategoryMembership::query()
                ->where('category_id', $category->id)
                ->where('status', PlayerCategoryMembership::STATUS_ACTIVE)
                ->with('player')
                ->get();

            foreach ($memberships as $membership) {
                $player = $membership->player;
                if (! $player) {
                    continue;
                }

                $attendances = DB::connection('tenant')
                    ->table('player_attendance')
                    ->leftJoin('trainings', 'trainings.id', '=', 'player_attendance.training_id')
                    ->leftJoin('matches', 'matches.id', '=', 'player_attendance.match_id')
                    ->where('player_attendance.player_id', $player->id)
                    ->where(fn ($q) => $q
                        ->where('trainings.category_id', $category->id)
                        ->orWhere('matches.category_id', $category->id))
                    ->select([
                        'player_attendance.id',
                        'player_attendance.status',
                        DB::raw('COALESCE(trainings.starts_at, matches.match_date) as event_date'),
                    ])
                    ->whereNotNull(DB::raw('COALESCE(trainings.starts_at, matches.match_date)'))
                    ->where(DB::raw('COALESCE(trainings.starts_at, matches.match_date)'), '<=', now())
                    ->orderByDesc('event_date')
                    ->orderByDesc('player_attendance.id')
                    ->get();

                if ($attendances->isEmpty()) {
                    continue;
                }

                // 1. Condición A: 3 ausencias seguidas sin justificar (status = 'absent')
                $isConsecutiveAbsences = false;
                if ($attendances->count() >= $consecutiveThreshold) {
                    $recentSubset = $attendances->take($consecutiveThreshold);
                    $isConsecutiveAbsences = $recentSubset->every(fn ($att) => $att->status === 'absent');
                }

                // 2. Condición B: asistencia del último mes < 50% con promedio histórico > 75%
                $isRateDrop = false;
                $oneMonthAgo = now()->subDays(30)->toDateTimeString();

                $recentAttendances = $attendances->filter(fn ($att) => $att->event_date >= $oneMonthAgo);
                $priorAttendances = $attendances->filter(fn ($att) => $att->event_date < $oneMonthAgo);

                $recentRate = 0.0;
                $priorRate = 0.0;

                if ($recentAttendances->isNotEmpty() && $priorAttendances->isNotEmpty()) {
                    $recentAttended = $recentAttendances->filter(fn ($att) => in_array($att->status, ['present', 'late'], true))->count();
                    $recentRate = ($recentAttended / $recentAttendances->count()) * 100;

                    $priorAttended = $priorAttendances->filter(fn ($att) => in_array($att->status, ['present', 'late'], true))->count();
                    $priorRate = ($priorAttended / $priorAttendances->count()) * 100;

                    if ($recentRate < $currentMax && $priorRate > $priorMin) {
                        $isRateDrop = true;
                    }
                }

                $conditionMet = $isConsecutiveAbsences || $isRateDrop;

                if (! $conditionMet) {
                    // Si la condición ya no se cumple (por ejemplo el jugador volvió a asistir),
                    // se resuelven las alertas previas abiertas para que pueda volver a alertar a futuro.
                    if (! $dryRun) {
                        AlertRecord::query()
                            ->where('alert_type', 'attendance_drop')
                            ->where('subject_type', 'player')
                            ->where('subject_id', $player->id)
                            ->whereNull('resolved_at')
                            ->update(['resolved_at' => now(), 'updated_at' => now()]);
                    }

                    continue;
                }

                $latest = $attendances->first();
                $dedupKey = "attendance_drop:{$player->id}:{$category->id}:{$latest->id}";

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

                $title = "Alerta de abandono: {$player->first_name} {$player->last_name}";
                if ($isConsecutiveAbsences && $isRateDrop) {
                    $body = "El jugador {$player->first_name} {$player->last_name} ({$category->name}) tiene {$consecutiveThreshold} ausencias seguidas sin justificar y su asistencia del último mes cayó al ".round($recentRate).'% (promedio anterior: '.round($priorRate).'%).';
                } elseif ($isConsecutiveAbsences) {
                    $body = "El jugador {$player->first_name} {$player->last_name} ({$category->name}) tiene {$consecutiveThreshold} ausencias seguidas sin justificar.";
                } else {
                    $body = "La asistencia de {$player->first_name} {$player->last_name} ({$category->name}) en el último mes es del ".round($recentRate).'% (su promedio anterior era del '.round($priorRate).'%).';
                }

                $data = [
                    'player_id' => $player->id,
                    'category_id' => $category->id,
                    'reason' => $isConsecutiveAbsences ? 'consecutive_absences' : 'attendance_drop',
                    'latest_attendance_id' => $latest->id,
                ];

                $this->channel->send(
                    $recipientIds,
                    'attendance_drop',
                    $title,
                    $body,
                    $data,
                );

                AlertRecord::query()->create([
                    'alert_type' => 'attendance_drop',
                    'subject_type' => 'player',
                    'subject_id' => $player->id,
                    'dedup_key' => $dedupKey,
                    'notified_at' => now(),
                ]);
            }
        }

        return $alertsGenerated;
    }
}
