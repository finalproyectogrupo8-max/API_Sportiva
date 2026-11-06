<?php

namespace App\Http\Controllers\Club;

use App\Http\Controllers\Club\Concerns\ChecksClubScope;
use App\Http\Controllers\Controller;
use App\Http\Requests\Club\StoreSeasonObjectiveRequest;
use App\Http\Resources\Club\SeasonObjectiveResource;
use App\Models\Club\Season;
use App\Models\Club\SeasonObjective;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/** Objetivos de la temporada, módulo "Proyecto" (TIZ-20). Los ve `project.view` y los edita `project.update`. */
class SeasonObjectiveController extends Controller
{
    use ChecksClubScope;

    /** Por defecto, los de la temporada en curso. `?season_id=` para otra y `?category_id=` para una categoría. */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorizeClub('project.view');
        // Temporada en curso: con alcance "sus deportes", la de su deporte o la del club.
        $seasonId = $request->integer('season_id') ?: Season::query()->visibleTo($this->access(), 'project.view')
            ->where('status', 'active')->orderByDesc('start_date')->value('id');
        $scoped = $this->access()->scopedCategoryIds('project.view');

        $objectives = SeasonObjective::query()
            ->with('category')
            ->where('season_id', $seasonId ?? 0)
            ->when($request->integer('category_id'), fn ($q, $id) => $q->where('category_id', $id))
            // Alcance "su deporte": los objetivos generales del club y los de sus categorías.
            ->when($scoped !== null, fn ($q) => $q->where(
                fn ($q) => $q->whereNull('category_id')->orWhereIn('category_id', $scoped ?: [0]),
            ))
            ->orderByRaw('category_id IS NOT NULL')
            ->orderBy('category_id')
            ->orderBy('id')
            ->get();

        return SeasonObjectiveResource::collection($objectives);
    }

    public function store(StoreSeasonObjectiveRequest $request): JsonResponse
    {
        $this->authorizeClub('project.update');
        $this->ensureCanEdit($request->validated('category_id'));
        $objective = SeasonObjective::create($request->validated());

        return SeasonObjectiveResource::make($objective->refresh()->load('category'))->response()->setStatusCode(201);
    }

    public function show(SeasonObjective $seasonObjective): SeasonObjectiveResource
    {
        $this->authorizeClub('project.view');
        $scoped = $this->access()->scopedCategoryIds('project.view');
        abort_if($scoped !== null && $seasonObjective->category_id !== null && ! in_array($seasonObjective->category_id, $scoped, true), 404);

        return SeasonObjectiveResource::make($seasonObjective->load('category'));
    }

    public function update(StoreSeasonObjectiveRequest $request, SeasonObjective $seasonObjective): SeasonObjectiveResource
    {
        $this->authorizeClub('project.update');
        $this->ensureCanEdit($seasonObjective->category_id);
        if ($request->has('category_id')) {
            $this->ensureCanEdit($request->validated('category_id'));
        }
        $seasonObjective->update($request->validated());

        return SeasonObjectiveResource::make($seasonObjective->refresh()->load('category'));
    }

    public function destroy(SeasonObjective $seasonObjective): Response
    {
        $this->authorizeClub('project.update');
        $this->ensureCanEdit($seasonObjective->category_id);
        $seasonObjective->delete();

        return response()->noContent();
    }

    /** Con alcance "sus deportes" edita los objetivos de sus categorías, no los generales del club. */
    private function ensureCanEdit(?int $categoryId): void
    {
        $scoped = $this->access()->scopedCategoryIds('project.update');

        abort_if($scoped !== null && ($categoryId === null || ! in_array($categoryId, $scoped, true)), 403);
    }
}
