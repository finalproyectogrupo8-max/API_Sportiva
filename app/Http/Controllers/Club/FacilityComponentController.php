<?php

namespace App\Http\Controllers\Club;

use App\Http\Controllers\Controller;
use App\Http\Requests\Club\IndexFacilityComponentRequest;
use App\Http\Requests\Club\StoreFacilityComponentRequest;
use App\Http\Requests\Club\UpdateFacilityComponentRequest;
use App\Http\Resources\Club\FacilityComponentResource;
use App\Models\Club\Facility;
use App\Models\Club\FacilityComponent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class FacilityComponentController extends Controller
{
    public function index(IndexFacilityComponentRequest $request, Facility $facility): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', FacilityComponent::class);

        $filters = $request->validated();

        $components = $facility->components()
            ->when($filters['condition_status'] ?? null, fn ($q, $status) => $q->where('condition_status', $status))
            ->when($filters['search'] ?? null, fn ($q, $search) => $q->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->paginate($filters['per_page'] ?? 20);

        return FacilityComponentResource::collection($components);
    }

    public function store(StoreFacilityComponentRequest $request, Facility $facility): JsonResponse
    {
        Gate::authorize('create', FacilityComponent::class);

        $component = $facility->components()->create($request->validated());

        return FacilityComponentResource::make($component->refresh())->response()->setStatusCode(201);
    }

    public function show(FacilityComponent $component): FacilityComponentResource
    {
        Gate::authorize('view', $component);

        return FacilityComponentResource::make($component->load('facility'));
    }

    public function update(UpdateFacilityComponentRequest $request, FacilityComponent $component): FacilityComponentResource
    {
        Gate::authorize('update', $component);

        $component->update($request->validated());

        return FacilityComponentResource::make($component->refresh());
    }

    public function destroy(FacilityComponent $component): Response
    {
        Gate::authorize('delete', $component);

        $component->delete();

        return response()->noContent();
    }
}
