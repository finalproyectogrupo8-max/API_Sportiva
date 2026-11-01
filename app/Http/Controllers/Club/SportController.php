<?php

namespace App\Http\Controllers\Club;

use App\Http\Controllers\Controller;
use App\Http\Requests\Club\IndexSportRequest;
use App\Http\Requests\Club\StoreSportRequest;
use App\Http\Requests\Club\UpdateSportRequest;
use App\Http\Resources\Club\SportResource;
use App\Models\Club\Sport;
use App\Support\ApiError;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class SportController extends Controller
{
    public function index(IndexSportRequest $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Sport::class);

        $filters = $request->validated();
        $sort = $filters['sort'] ?? 'name';
        $column = ltrim($sort, '-');
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';

        $sports = Sport::query()
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where('name', 'like', "%{$search}%"))
            ->when(array_key_exists('is_active', $filters), fn ($query) => $query->where('is_active', $filters['is_active']))
            ->orderBy($column, $direction)
            ->orderBy('id')
            ->paginate($filters['per_page'] ?? 20);

        return SportResource::collection($sports);
    }

    public function store(StoreSportRequest $request): JsonResponse
    {
        Gate::authorize('create', Sport::class);

        $sport = Sport::create($request->validated());

        return SportResource::make($sport->refresh())->response()->setStatusCode(201);
    }

    public function show(Sport $sport): SportResource
    {
        Gate::authorize('view', $sport);

        return SportResource::make($sport);
    }

    public function update(UpdateSportRequest $request, Sport $sport): SportResource
    {
        Gate::authorize('update', $sport);

        $sport->update($request->validated());

        return SportResource::make($sport->refresh());
    }

    /** La baja desactiva el deporte (D12). Con categorías activas responde 409 (D11). */
    public function destroy(Sport $sport): Response|JsonResponse
    {
        Gate::authorize('delete', $sport);

        if ($sport->categories()->where('is_active', true)->exists()) {
            return ApiError::conflict('resource_in_use');
        }

        $sport->update(['is_active' => false]);

        return response()->noContent();
    }
}
