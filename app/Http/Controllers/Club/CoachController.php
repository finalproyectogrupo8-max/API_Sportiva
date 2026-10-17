<?php

namespace App\Http\Controllers\Club;

use App\Http\Controllers\Controller;
use App\Http\Requests\Club\IndexCoachRequest;
use App\Http\Requests\Club\StoreCoachRequest;
use App\Http\Requests\Club\UpdateCoachRequest;
use App\Http\Resources\Club\CoachResource;
use App\Models\Club\Coach;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class CoachController extends Controller
{
    public function index(IndexCoachRequest $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Coach::class);

        $filters = $request->validated();
        $sort = $filters['sort'] ?? 'last_name';
        $column = ltrim($sort, '-');
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';

        $coaches = Coach::query()
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(function ($query) use ($search) {
                $query->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
            }))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->orderBy($column, $direction)
            ->orderBy('id')
            ->paginate($filters['per_page'] ?? 20);

        return CoachResource::collection($coaches);
    }

    public function store(StoreCoachRequest $request): JsonResponse
    {
        Gate::authorize('create', Coach::class);

        $coach = Coach::create($request->validated());

        return CoachResource::make($coach)->response()->setStatusCode(201);
    }

    public function show(Coach $coach): CoachResource
    {
        Gate::authorize('view', $coach);

        return CoachResource::make($coach);
    }

    public function update(UpdateCoachRequest $request, Coach $coach): CoachResource
    {
        Gate::authorize('update', $coach);

        $coach->update($request->validated());

        return CoachResource::make($coach->refresh());
    }

    public function destroy(Coach $coach): Response
    {
        Gate::authorize('delete', $coach);

        $coach->delete();

        return response()->noContent();
    }
}
