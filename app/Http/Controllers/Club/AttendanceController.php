<?php

namespace App\Http\Controllers\Club;

use App\Http\Controllers\Club\Concerns\ChecksClubScope;
use App\Http\Controllers\Controller;
use App\Http\Requests\Club\RecordAttendanceRequest;
use App\Models\Club\GameMatch;
use App\Models\Club\MatchCallup;
use App\Models\Club\Player;
use App\Models\Club\PlayerAttendance;
use App\Models\Club\PlayerCategoryMembership;
use App\Models\Club\Training;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Asistencia de partidos y entrenamientos (TIZ-16). GET devuelve la lista con lo cargado; PUT
 * guarda el lote completo y es idempotente (un registro por jugador y evento).
 *
 * En un partido la lista son los convocados (y la convocatoria solo admite habilitados); en un
 * entrenamiento, el plantel activo de la categoría.
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
     * Los convocados del partido (o el plantel activo del entrenamiento) más quien ya tenga
     * asistencia cargada, para poder corregirla. `status` es null si todavía no se tomó.
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
        $callups = $event instanceof GameMatch
            ? MatchCallup::query()->where('match_id', $event->id)->get()->keyBy('player_id')
            : null;
        $squad = $callups?->keys() ?? $memberships->keys();

        $players = Player::query()
            ->whereIn('id', $squad->merge($recorded->keys())->unique())
            ->with('photo')
            ->orderBy('last_name')->orderBy('first_name')
            ->get();

        return response()->json([
            'data' => $players->map(fn (Player $player) => $this->row(
                $player,
                $memberships->get($player->id),
                $callups?->get($player->id),
                $recorded->get($player->id),
            ))->values(),
        ]);
    }

    /**
     * @return array{player_id: int, player_name: string, shirt_number: int|null, photo_document_id: int|null, status: string|null, notes: string|null}
     */
    private function row(Player $player, ?PlayerCategoryMembership $membership, ?MatchCallup $callup, ?PlayerAttendance $attendance): array
    {
        return [
            'player_id' => $player->id,
            'player_name' => trim($player->first_name.' '.$player->last_name),
            /** En un partido, el número de la convocatoria; si no tiene, el del plantel. */
            'shirt_number' => $callup?->shirt_number ?? $membership?->shirt_number,
            /** Documento con la foto del jugador (se baja con `GET /documents/{id}/download`). */
            'photo_document_id' => $player->photo?->id,
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
        $isMatch = $event instanceof GameMatch;
        $squad = $isMatch
            ? MatchCallup::query()->where('match_id', $event->id)->pluck('player_id')
            : PlayerCategoryMembership::query()
                ->where('category_id', $event->category_id)
                ->where('status', PlayerCategoryMembership::STATUS_ACTIVE)
                ->pluck('player_id');
        $allowed = $squad->merge(PlayerAttendance::query()->where($column, $event->id)->pluck('player_id'))->all();
        $message = $isMatch ? 'El jugador no está convocado a este partido.' : 'El jugador no está en el plantel de la categoría.';

        $errors = $records
            ->filter(fn ($record) => ! in_array($record['player_id'], $allowed))
            ->mapWithKeys(fn ($record, $i) => ["records.{$i}.player_id" => $message]);
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
