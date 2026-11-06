<?php

namespace App\Http\Controllers\Club;

use App\Http\Controllers\Controller;
use App\Http\Requests\Club\IndexPositionRequest;
use App\Http\Requests\Club\StorePositionRequest;
use App\Http\Requests\Club\UpdatePositionRequest;
use App\Http\Resources\Club\PositionResource;
use App\Models\Club\Position;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class PositionController extends Controller
{
    public function index(IndexPositionRequest $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Position::class);

        $filters = $request->validated();
        $sort = $filters['sort'] ?? 'name';
        $column = ltrim($sort, '-');
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';

        // Alcance "su deporte": solo las posiciones de sus deportes.
        $sportIds = $request->user()->clubAccess()->scopedSportIds('positions.view');

        $positions = Position::query()
            ->with('sport')
            ->when($sportIds !== null, fn ($query) => $query->whereIn('sport_id', $sportIds ?: [0]))
            ->when($filters['sport_id'] ?? null, fn ($query, int $sportId) => $query->where('sport_id', $sportId))
            ->when($filters['search'] ?? null, fn ($query, string $search) => $query->where('name', 'like', "%{$search}%"))
            ->where('is_active', $filters['is_active'] ?? true)
            ->orderBy($column, $direction)
            ->orderBy('id')
            ->paginate($filters['per_page'] ?? 20);

        return PositionResource::collection($positions);
    }

    public function store(StorePositionRequest $request): JsonResponse
    {
        Gate::authorize('create', Position::class);

        $position = Position::create($request->validated());

        return PositionResource::make($position->load('sport'))->response()->setStatusCode(201);
    }

    public function show(Position $position): PositionResource
    {
        Gate::authorize('view', $position);
        $sportIds = request()->user()->clubAccess()->scopedSportIds('positions.view');
        abort_if($sportIds !== null && ! in_array($position->sport_id, $sportIds, true), 404);

        return PositionResource::make($position->load('sport'));
    }

    public function update(UpdatePositionRequest $request, Position $position): PositionResource
    {
        Gate::authorize('update', $position);

        $position->update($request->validated());

        return PositionResource::make($position->refresh()->load('sport'));
    }

    public function destroy(Position $position): Response
    {
        Gate::authorize('delete', $position);

        $position->update(['is_active' => false]);

        return response()->noContent();
    }
}
