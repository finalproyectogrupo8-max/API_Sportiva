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
        $seasonId = $request->integer('season_id') ?: Season::query()->where('status', 'active')->orderByDesc('start_date')->value('id');

        $objectives = SeasonObjective::query()
            ->with('category')
            ->where('season_id', $seasonId ?? 0)
            ->when($request->integer('category_id'), fn ($q, $id) => $q->where('category_id', $id))
            ->orderByRaw('category_id IS NOT NULL')
            ->orderBy('category_id')
            ->orderBy('id')
            ->get();

        return SeasonObjectiveResource::collection($objectives);
    }

    public function store(StoreSeasonObjectiveRequest $request): JsonResponse
    {
        $this->authorizeClub('project.update');
        $objective = SeasonObjective::create($request->validated());

        return SeasonObjectiveResource::make($objective->refresh()->load('category'))->response()->setStatusCode(201);
    }

    public function show(SeasonObjective $seasonObjective): SeasonObjectiveResource
    {
        $this->authorizeClub('project.view');

        return SeasonObjectiveResource::make($seasonObjective->load('category'));
    }

    public function update(StoreSeasonObjectiveRequest $request, SeasonObjective $seasonObjective): SeasonObjectiveResource
    {
        $this->authorizeClub('project.update');
        $seasonObjective->update($request->validated());

        return SeasonObjectiveResource::make($seasonObjective->refresh()->load('category'));
    }

    public function destroy(SeasonObjective $seasonObjective): Response
    {
        $this->authorizeClub('project.update');
        $seasonObjective->delete();

        return response()->noContent();
    }
}
