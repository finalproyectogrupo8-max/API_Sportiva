<?php

namespace App\Http\Controllers\Club;

use App\Http\Controllers\Controller;
use App\Http\Requests\Club\IndexSeasonRequest;
use App\Http\Requests\Club\StoreSeasonRequest;
use App\Http\Requests\Club\UpdateSeasonRequest;
use App\Http\Resources\Club\SeasonResource;
use App\Models\Club\Season;
use App\Support\ApiError;
use Illuminate\Database\QueryException;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class SeasonController extends Controller
{
    public function index(IndexSeasonRequest $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Season::class);

        $filters = $request->validated();
        $sort = $filters['sort'] ?? 'start_date';
        $column = ltrim($sort, '-');
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';

        $seasons = Season::query()
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where('name', 'like', "%{$search}%"))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->orderBy($column, $direction)
            ->orderBy('id')
            ->paginate($filters['per_page'] ?? 20);

        return SeasonResource::collection($seasons);
    }

    public function store(StoreSeasonRequest $request): JsonResponse
    {
        Gate::authorize('create', Season::class);

        $season = Season::create($request->validated());

        return SeasonResource::make($season)->response()->setStatusCode(201);
    }

    public function show(Season $season): SeasonResource
    {
        Gate::authorize('view', $season);

        return SeasonResource::make($season);
    }

    public function update(UpdateSeasonRequest $request, Season $season): SeasonResource
    {
        Gate::authorize('update', $season);

        $season->update($request->validated());

        return SeasonResource::make($season->refresh());
    }

    public function destroy(Season $season): Response|JsonResponse
    {
        Gate::authorize('delete', $season);

        if ($season->categories()->exists()) {
            return ApiError::conflict('resource_in_use');
        }

        try {
            $season->delete();
        } catch (QueryException $exception) {
            if ($exception->getCode() === '23000') {
                return ApiError::conflict('resource_in_use');
            }

            throw $exception;
        }

        return response()->noContent();
    }
}
