<?php

namespace App\Http\Controllers\Club;

use App\Http\Controllers\Club\Concerns\ChecksClubScope;
use App\Http\Controllers\Controller;
use App\Models\Club\Category;
use App\Models\Club\GameMatch;
use App\Models\Club\PhysicalEvaluation;
use App\Models\Club\Player;
use App\Models\Club\PlayerCategoryMembership;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Indicadores para el módulo Análisis (TIZ-20), calculados en la API para no traer todo al front:
 * - asistencia: presentes + tardes sobre lo registrado (entrenamientos y partidos);
 * - resultados: partidos jugados, ganados, empatados, perdidos y goles;
 * - evaluaciones físicas cargadas.
 * El período es `?from=&to=` (por defecto, los últimos 90 días).
 */
class AnalyticsController extends Controller
{
    use ChecksClubScope;

    private const PRESENT = ['present', 'late'];

    /** Una fila por categoría activa visible. */
    public function categories(Request $request): JsonResponse
    {
        $this->authorizeClub('physical_evaluations.view');
        [$from, $to] = $this->period($request);
        $scoped = $this->access()->scopedCategoryIds('physical_evaluations.view');

        $categories = Category::query()
            ->where('is_active', true)
            ->when($scoped !== null, fn ($q) => $q->whereIn('id', $scoped ?: [0]))
            ->orderBy('name')
            ->get();

        $rows = $categories->map(function (Category $category) use ($from, $to) {
            $attendance = $this->attendance($category->id, $from, $to)->get();
            $matches = GameMatch::query()
                ->where('category_id', $category->id)
                ->where('status', 'played')
                ->whereBetween('match_date', [$from, $to])
                ->whereNotNull('our_score')->whereNotNull('opponent_score')
                ->orderBy('match_date')
                ->get(['our_score', 'opponent_score', 'match_date', 'opponent']);

            return [
                'category_id' => $category->id,
                'category_name' => $category->name,
                'players' => PlayerCategoryMembership::query()->where('category_id', $category->id)->where('status', PlayerCategoryMembership::STATUS_ACTIVE)->count(),
                'attendance' => $this->rate($attendance),
                'sessions' => $attendance->map(fn ($r) => $r->training_id ? "t{$r->training_id}" : "m{$r->match_id}")->unique()->count(),
                'results' => $this->results($matches),
                'evaluations' => PhysicalEvaluation::query()->where('category_id', $category->id)->whereBetween('evaluated_at', [$from, $to])->count(),
            ];
        });

        return response()->json(['data' => $rows->values(), 'meta' => ['from' => $from->toDateString(), 'to' => $to->toDateString()]]);
    }

    /**
     * Tablero de una categoría: totales, asistencia semana a semana, distribución por estado y los
     * partidos jugados uno por uno, para los gráficos del módulo Análisis.
     */
    public function category(Request $request, Category $category): JsonResponse
    {
        $this->authorizeClub('physical_evaluations.view');
        $this->ensureCategoryInScope($category, 'physical_evaluations.view');
        [$from, $to] = $this->period($request);

        $attendance = $this->attendance($category->id, $from, $to)->get();
        $matches = GameMatch::query()
            ->where('category_id', $category->id)
            ->where('status', 'played')
            ->whereBetween('match_date', [$from, $to])
            ->whereNotNull('our_score')->whereNotNull('opponent_score')
            ->orderBy('match_date')
            ->get(['id', 'our_score', 'opponent_score', 'match_date', 'opponent', 'is_home']);

        // Semanas de lunes a domingo, todas las del período (las sin registros van con rate null).
        $weeks = [];
        for ($week = $from->startOfWeek(); $week <= $to; $week = $week->addWeek()) {
            $weeks[$week->toDateString()] = collect();
        }
        foreach ($attendance as $row) {
            $key = CarbonImmutable::parse($row->starts_at)->startOfWeek()->toDateString();
            if (isset($weeks[$key])) {
                $weeks[$key]->push($row);
            }
        }

        return response()->json(['data' => [
            'category_id' => $category->id,
            'category_name' => $category->name,
            'players' => PlayerCategoryMembership::query()->where('category_id', $category->id)->where('status', PlayerCategoryMembership::STATUS_ACTIVE)->count(),
            'attendance' => $this->rate($attendance),
            'sessions' => $attendance->map(fn ($r) => $r->training_id ? "t{$r->training_id}" : "m{$r->match_id}")->unique()->count(),
            /** Registros de asistencia por estado: present, late, excused, absent. */
            'by_status' => collect(['present', 'late', 'excused', 'absent'])->mapWithKeys(fn ($s) => [$s => $attendance->where('status', $s)->count()]),
            /** Una fila por semana (lunes) del período. */
            'weekly' => collect($weeks)->map(fn (Collection $rows, string $week) => ['week_start' => $week, ...$this->rate($rows)])->values(),
            'results' => $this->results($matches),
            /** Partidos jugados del período, del más viejo al más nuevo. */
            'matches' => $matches->map(fn (GameMatch $m) => [
                'id' => $m->id,
                'date' => CarbonImmutable::parse($m->match_date)->toIso8601String(),
                'opponent' => $m->opponent,
                'is_home' => (bool) $m->is_home,
                'our_score' => (int) $m->our_score,
                'opponent_score' => (int) $m->opponent_score,
                'result' => $m->our_score > $m->opponent_score ? 'W' : ($m->our_score === $m->opponent_score ? 'D' : 'L'),
            ])->values(),
            'evaluations' => PhysicalEvaluation::query()->where('category_id', $category->id)->whereBetween('evaluated_at', [$from, $to])->count(),
        ], 'meta' => ['from' => $from->toDateString(), 'to' => $to->toDateString()]]);
    }

    /** Una fila por jugador activo de la categoría: asistencia y evaluaciones. */
    public function players(Request $request, Category $category): JsonResponse
    {
        $this->authorizeClub('physical_evaluations.view');
        $this->ensureCategoryInScope($category, 'physical_evaluations.view');
        [$from, $to] = $this->period($request);

        $playerIds = PlayerCategoryMembership::query()->where('category_id', $category->id)->where('status', PlayerCategoryMembership::STATUS_ACTIVE)->pluck('player_id');
        $attendance = $this->attendance($category->id, $from, $to)->whereIn('pa.player_id', $playerIds)->get()->groupBy('player_id');
        $evaluations = PhysicalEvaluation::query()
            ->whereIn('player_id', $playerIds)
            ->selectRaw('player_id, count(*) as total, max(evaluated_at) as last_at')
            ->groupBy('player_id')
            ->get()
            ->keyBy('player_id');

        $rows = Player::query()->whereIn('id', $playerIds)->orderBy('last_name')->orderBy('first_name')->get()
            ->map(function (Player $player) use ($attendance, $evaluations) {
                $records = $attendance->get($player->id, collect());
                $eval = $evaluations->get($player->id);

                return [
                    'player_id' => $player->id,
                    'player_name' => trim($player->first_name.' '.$player->last_name),
                    'attendance' => $this->rate($records),
                    'by_status' => collect(['present', 'late', 'excused', 'absent'])->mapWithKeys(fn ($s) => [$s => $records->where('status', $s)->count()]),
                    /** Faltas sin aviso seguidas en las últimas sesiones registradas. */
                    'absent_streak' => $records->sortByDesc('starts_at')->values()->takeWhile(fn ($r) => $r->status === 'absent')->count(),
                    'evaluations' => (int) ($eval->total ?? 0),
                    'last_evaluated_at' => $eval?->last_at ? CarbonImmutable::parse($eval->last_at)->toIso8601String() : null,
                ];
            });

        return response()->json(['data' => $rows->values(), 'meta' => ['from' => $from->toDateString(), 'to' => $to->toDateString()]]);
    }

    /** @return array{0: CarbonImmutable, 1: CarbonImmutable} */
    private function period(Request $request): array
    {
        $to = $request->date('to') ? CarbonImmutable::parse($request->date('to'))->endOfDay() : CarbonImmutable::now()->endOfDay();
        $from = $request->date('from') ? CarbonImmutable::parse($request->date('from'))->startOfDay() : $to->subDays(90)->startOfDay();

        return [$from, $to];
    }

    /** Asistencia registrada de la categoría en el período, con la fecha de cada sesión. */
    private function attendance(int $categoryId, CarbonImmutable $from, CarbonImmutable $to)
    {
        return DB::connection('tenant')->table('player_attendance as pa')
            ->leftJoin('trainings as t', 't.id', '=', 'pa.training_id')
            ->leftJoin('matches as m', 'm.id', '=', 'pa.match_id')
            ->where(fn ($q) => $q->where('t.category_id', $categoryId)->orWhere('m.category_id', $categoryId))
            ->whereNull('t.deleted_at')->whereNull('m.deleted_at')
            ->whereRaw('COALESCE(t.starts_at, m.match_date) BETWEEN ? AND ?', [$from, $to])
            ->select('pa.player_id', 'pa.status', 'pa.training_id', 'pa.match_id', DB::raw('COALESCE(t.starts_at, m.match_date) as starts_at'));
    }

    /** @return array{recorded: int, present: int, rate: int|null} */
    private function rate(Collection $records): array
    {
        $present = $records->whereIn('status', self::PRESENT)->count();

        return ['recorded' => $records->count(), 'present' => $present, 'rate' => $records->isEmpty() ? null : (int) round($present / $records->count() * 100)];
    }

    private function results(Collection $matches): array
    {
        return [
            'played' => $matches->count(),
            'won' => $matches->filter(fn ($m) => $m->our_score > $m->opponent_score)->count(),
            'drawn' => $matches->filter(fn ($m) => $m->our_score === $m->opponent_score)->count(),
            'lost' => $matches->filter(fn ($m) => $m->our_score < $m->opponent_score)->count(),
            'goals_for' => (int) $matches->sum('our_score'),
            'goals_against' => (int) $matches->sum('opponent_score'),
            /** Los últimos 5, del más viejo al más nuevo: `W`, `D` o `L`. */
            'form' => $matches->slice(-5)->map(fn ($m) => $m->our_score > $m->opponent_score ? 'W' : ($m->our_score === $m->opponent_score ? 'D' : 'L'))->values(),
        ];
    }
}
