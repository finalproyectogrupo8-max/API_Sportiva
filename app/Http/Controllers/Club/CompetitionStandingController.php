<?php

namespace App\Http\Controllers\Club;

use App\Http\Controllers\Club\Concerns\ChecksClubScope;
use App\Http\Controllers\Controller;
use App\Http\Requests\Club\UpdateCompetitionStandingsRequest;
use App\Models\Club\Competition;
use App\Models\Club\CompetitionStanding;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Tabla de posiciones de una competencia por categoría, módulo "Proyecto" (TIZ-20). Se carga a mano
 * y se guarda entera (PUT reemplaza todas las filas de esa categoría).
 */
class CompetitionStandingController extends Controller
{
    use ChecksClubScope;

    /** `?category_id=` para una sola categoría; sin él, todas las de la competencia. */
    public function index(Request $request, Competition $competition): JsonResponse
    {
        $this->authorizeClub('project.view');

        $rows = CompetitionStanding::query()
            ->where('competition_id', $competition->id)
            ->when($request->integer('category_id'), fn ($q, $id) => $q->where('category_id', $id))
            ->orderBy('category_id')->orderBy('position')
            ->get();

        return response()->json(['data' => $rows->map(fn (CompetitionStanding $row) => $this->row($row))->values()]);
    }

    public function update(UpdateCompetitionStandingsRequest $request, Competition $competition): JsonResponse
    {
        $this->authorizeClub('project.update');
        $data = $request->validated();

        if (! $competition->categories()->whereKey($data['category_id'])->exists()) {
            throw ValidationException::withMessages(['category_id' => 'La categoría no juega esta competencia.']);
        }

        $rows = DB::connection('tenant')->transaction(function () use ($competition, $data) {
            CompetitionStanding::query()->where('competition_id', $competition->id)->where('category_id', $data['category_id'])->delete();

            return collect($data['rows'])->values()->map(fn (array $row, int $i) => CompetitionStanding::create([
                ...collect($row)->only(['team_name', 'is_own', ...CompetitionStanding::COUNTERS])->all(),
                'competition_id' => $competition->id,
                'category_id' => $data['category_id'],
                'position' => $i + 1,
            ]));
        });

        return response()->json(['data' => $rows->map(fn (CompetitionStanding $row) => $this->row($row->refresh()))->values()]);
    }

    /** @return array<string, mixed> */
    private function row(CompetitionStanding $row): array
    {
        return [
            'id' => $row->id,
            'category_id' => $row->category_id,
            'position' => $row->position,
            'team_name' => $row->team_name,
            /** La fila del club. */
            'is_own' => $row->is_own,
            'played' => $row->played,
            'won' => $row->won,
            'drawn' => $row->drawn,
            'lost' => $row->lost,
            'goals_for' => $row->goals_for,
            'goals_against' => $row->goals_against,
            'points' => $row->points,
        ];
    }
}
