<?php

namespace App\Http\Controllers\Club;

use App\Http\Controllers\Club\Concerns\ChecksClubScope;
use App\Http\Controllers\Controller;
use App\Http\Requests\Club\RecordAttendanceRequest;
use App\Models\Club\GameMatch;
use App\Models\Club\Player;
use App\Models\Club\PlayerAttendance;
use App\Models\Club\PlayerCategoryMembership;
use App\Models\Club\Training;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Asistencia de partidos y entrenamientos (TIZ-16). GET devuelve el plantel con lo cargado; PUT
 * guarda el lote completo y es idempotente (un registro por jugador y evento).
 */
class AttendanceController extends Controller
{
    use ChecksClubScope;

    public function showMatch(GameMatch $match): JsonResponse
    {
        return $this->roster($match, 'match_id');
    }

    public function recordMatch(RecordAttendanceRequest $request, GameMatch $match): JsonResponse
    {
        return $this->record($request, $match, 'match_id');
    }

    public function showTraining(Training $training): JsonResponse
    {
        return $this->roster($training, 'training_id');
    }

    public function recordTraining(RecordAttendanceRequest $request, Training $training): JsonResponse
    {
        return $this->record($request, $training, 'training_id');
    }

    /**
     * El plantel activo de la categoría más quien ya tenga asistencia cargada (por ejemplo, alguien
     * que cambió de categoría después). `status` es null si todavía no se tomó.
     */
    private function roster(GameMatch|Training $event, string $column): JsonResponse
    {
        $this->authorizeClub('attendance.view');
        $this->ensureCategoryInScope($event->category, 'attendance.view');

        $recorded = PlayerAttendance::query()->where($column, $event->id)->get()->keyBy('player_id');
        $memberships = PlayerCategoryMembership::query()
            ->where('category_id', $event->category_id)
            ->where('status', PlayerCategoryMembership::STATUS_ACTIVE)
            ->get()
            ->keyBy('player_id');

        $players = Player::query()
            ->whereIn('id', $memberships->keys()->merge($recorded->keys())->unique())
            ->orderBy('last_name')->orderBy('first_name')
            ->get();

        return response()->json([
            'data' => $players->map(fn (Player $player) => $this->row(
                $player,
                $memberships->get($player->id),
                $recorded->get($player->id),
            ))->values(),
        ]);
    }

    /**
     * @return array{player_id: int, player_name: string, shirt_number: int|null, status: string|null, notes: string|null}
     */
    private function row(Player $player, ?PlayerCategoryMembership $membership, ?PlayerAttendance $attendance): array
    {
        return [
            'player_id' => $player->id,
            'player_name' => trim($player->first_name.' '.$player->last_name),
            'shirt_number' => $membership?->shirt_number,
            /** `present`, `absent`, `excused`, `late` o null si todavía no se tomó. */
            'status' => $attendance?->status,
            'notes' => $attendance?->notes,
        ];
    }

    private function record(RecordAttendanceRequest $request, GameMatch|Training $event, string $column): JsonResponse
    {
        $this->authorizeClub('attendance.record');
        $this->ensureCategoryInScope($event->category, 'attendance.record');

        $records = collect($request->validated('records'));
        $allowed = PlayerCategoryMembership::query()
            ->where('category_id', $event->category_id)
            ->where('status', PlayerCategoryMembership::STATUS_ACTIVE)
            ->pluck('player_id')
            ->merge(PlayerAttendance::query()->where($column, $event->id)->pluck('player_id'))
            ->all();

        $errors = $records
            ->filter(fn ($record) => ! in_array($record['player_id'], $allowed))
            ->mapWithKeys(fn ($record, $i) => ["records.{$i}.player_id" => 'El jugador no está en el plantel de la categoría.']);
        if ($errors->isNotEmpty()) {
            throw ValidationException::withMessages($errors->all());
        }

        $clubUserId = $request->user()->currentClubUser()?->id;
        abort_unless($clubUserId, 403);

        DB::connection('tenant')->transaction(function () use ($records, $event, $column, $clubUserId) {
            foreach ($records as $record) {
                PlayerAttendance::query()->updateOrCreate(
                    ['player_id' => $record['player_id'], $column => $event->id],
                    ['status' => $record['status'], 'notes' => $record['notes'] ?? null, 'recorded_by' => $clubUserId],
                );
            }
        });

        return $this->roster($event, $column);
    }
}
