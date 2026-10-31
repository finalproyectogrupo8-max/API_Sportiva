<?php

namespace App\Http\Controllers\Club;

use App\Http\Controllers\Controller;
use App\Http\Requests\Club\IndexFacilityRequest;
use App\Http\Requests\Club\StoreFacilityRequest;
use App\Http\Requests\Club\UpdateFacilityRequest;
use App\Http\Resources\Club\FacilityResource;
use App\Models\Club\Facility;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class FacilityController extends Controller
{
    public function index(IndexFacilityRequest $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Facility::class);

        $filters = $request->validated();
        $sort = $filters['sort'] ?? 'name';
        $column = ltrim($sort, '-');
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';

        $facilities = Facility::query()
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('location', 'like', "%{$search}%");
                });
            })
            ->when($filters['type'] ?? null, fn ($query, $type) => $query->where('type', $type))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->orderBy($column, $direction)
            ->orderBy('id')
            ->paginate($filters['per_page'] ?? 20);

        return FacilityResource::collection($facilities);
    }

    public function store(StoreFacilityRequest $request): JsonResponse
    {
        Gate::authorize('create', Facility::class);

        $facility = Facility::create($request->validated());

        return FacilityResource::make($facility->refresh())->response()->setStatusCode(201);
    }

    public function show(Facility $facility): FacilityResource
    {
        Gate::authorize('view', $facility);

        return FacilityResource::make($facility->load('components'));
    }

    public function update(UpdateFacilityRequest $request, Facility $facility): FacilityResource
    {
        Gate::authorize('update', $facility);

        $facility->update($request->validated());

        return FacilityResource::make($facility->refresh());
    }

    public function destroy(Facility $facility): Response
    {
        Gate::authorize('delete', $facility);

        $facility->delete();

        return response()->noContent();
    }
}
