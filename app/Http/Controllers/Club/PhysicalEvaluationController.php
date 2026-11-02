<?php

namespace App\Http\Controllers\Club;

use App\Http\Controllers\Club\Concerns\ChecksClubScope;
use App\Http\Controllers\Controller;
use App\Http\Requests\Club\IndexPhysicalEvaluationRequest;
use App\Http\Requests\Club\StorePhysicalEvaluationRequest;
use App\Http\Resources\Club\PhysicalEvaluationResource;
use App\Models\Club\Category;
use App\Models\Club\PhysicalEvaluation;
use App\Models\Club\Player;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Evaluaciones físicas (TIZ-20). Son datos de salud de personas, muchas veces menores: solo las
 * ven los roles con `physical_evaluations.view` y, con alcance `own`, solo de sus categorías.
 */
class PhysicalEvaluationController extends Controller
{
    use ChecksClubScope;

    private const RELATIONS = ['player', 'category', 'evaluator', 'measurements'];

    public function index(IndexPhysicalEvaluationRequest $request): AnonymousResourceCollection
    {
        $this->authorizeClub('physical_evaluations.view');
        $filters = $request->validated();

        $evaluations = PhysicalEvaluation::query()
            ->visibleTo($this->access(), 'physical_evaluations.view')
            ->with(self::RELATIONS)
            ->when($filters['player_id'] ?? null, fn ($q, $id) => $q->where('player_id', $id))
            ->when($filters['category_id'] ?? null, fn ($q, $id) => $q->where('category_id', $id))
            ->when($filters['metric_key'] ?? null, fn ($q, $key) => $q->whereHas('measurements', fn ($m) => $m->where('metric_key', $key)))
            ->when($filters['from'] ?? null, fn ($q, $from) => $q->where('evaluated_at', '>=', $from))
            ->when($filters['to'] ?? null, fn ($q, $to) => $q->where('evaluated_at', '<', date('Y-m-d', strtotime($to.' +1 day'))))
            ->orderBy('evaluated_at', ($filters['sort'] ?? '-evaluated_at') === 'evaluated_at' ? 'asc' : 'desc')
            ->orderBy('id')
            ->paginate($filters['per_page'] ?? 20);

        return PhysicalEvaluationResource::collection($evaluations);
    }

    public function store(StorePhysicalEvaluationRequest $request): JsonResponse
    {
        $this->authorizeClub('physical_evaluations.create');
        $data = $request->validated();
        $player = Player::findOrFail($data['player_id']);
        $data['category_id'] = $this->categoryFor($player, $data['category_id'] ?? null, 'physical_evaluations.create');
        $data['evaluator_coach_id'] ??= $this->currentCoachId();

        $evaluation = DB::connection('tenant')->transaction(function () use ($data) {
            $evaluation = PhysicalEvaluation::create($data);
            $evaluation->measurements()->createMany($this->measurements($data['measurements']));

            return $evaluation;
        });

        return PhysicalEvaluationResource::make($evaluation->load(self::RELATIONS))->response()->setStatusCode(201);
    }

    public function show(PhysicalEvaluation $physicalEvaluation): PhysicalEvaluationResource
    {
        $this->authorizeClub('physical_evaluations.view');
        $this->ensureVisible($physicalEvaluation, 'physical_evaluations.view');

        return PhysicalEvaluationResource::make($physicalEvaluation->load(self::RELATIONS));
    }

    public function update(StorePhysicalEvaluationRequest $request, PhysicalEvaluation $physicalEvaluation): PhysicalEvaluationResource
    {
        $this->authorizeClub('physical_evaluations.update');
        $this->ensureVisible($physicalEvaluation, 'physical_evaluations.update');
        $data = $request->validated();
        if (array_key_exists('category_id', $data) && $data['category_id'] !== null) {
            $this->ensureCategoryInScope(Category::findOrFail($data['category_id']), 'physical_evaluations.update');
        }

        DB::connection('tenant')->transaction(function () use ($physicalEvaluation, $data) {
            $physicalEvaluation->update(collect($data)->except('measurements')->all());
            if (isset($data['measurements'])) {
                $physicalEvaluation->measurements()->delete();
                $physicalEvaluation->measurements()->createMany($this->measurements($data['measurements']));
            }
        });

        return PhysicalEvaluationResource::make($physicalEvaluation->refresh()->load(self::RELATIONS));
    }

    public function destroy(PhysicalEvaluation $physicalEvaluation): Response
    {
        $this->authorizeClub('physical_evaluations.delete');
        $this->ensureVisible($physicalEvaluation, 'physical_evaluations.delete');

        $physicalEvaluation->delete();

        return response()->noContent();
    }

    /** La categoría de la evaluación: la indicada o la actual del jugador. Tiene que estar en el alcance del usuario. */
    private function categoryFor(Player $player, ?int $categoryId, string $permission): int
    {
        $categoryId ??= $player->activeMemberships()->orderByDesc('start_date')->value('category_id');
        if (! $categoryId) {
            throw ValidationException::withMessages(['category_id' => 'El jugador no está en ninguna categoría: indicá en cuál se lo evaluó.']);
        }
        $this->ensureCategoryInScope(Category::findOrFail($categoryId), $permission);

        return $categoryId;
    }

    private function currentCoachId(): int
    {
        $coachId = request()->user()->currentClubUser()?->coach?->id;
        if (! $coachId) {
            throw ValidationException::withMessages(['evaluator_coach_id' => 'Indicá qué profe o preparador hizo la evaluación.']);
        }

        return $coachId;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function measurements(array $rows): array
    {
        return array_map(fn (array $row) => [
            'metric_key' => $row['metric_key'],
            'metric_name' => $row['metric_name'],
            'numeric_value' => $row['numeric_value'] ?? null,
            'unit' => $row['unit'] ?? null,
            'text_value' => $row['text_value'] ?? null,
            'notes' => $row['notes'] ?? null,
        ], $rows);
    }

    private function ensureVisible(PhysicalEvaluation $evaluation, string $permission): void
    {
        abort_unless(PhysicalEvaluation::query()->visibleTo($this->access(), $permission)->whereKey($evaluation->getKey())->exists(), 404);
    }
}
