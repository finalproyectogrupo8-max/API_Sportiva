<?php

namespace App\Http\Controllers\Club;

use App\Http\Controllers\Controller;
use App\Http\Requests\Club\IndexStorageLocationRequest;
use App\Http\Requests\Club\StoreStorageLocationRequest;
use App\Http\Requests\Club\UpdateStorageLocationRequest;
use App\Http\Resources\Club\StorageLocationResource;
use App\Models\Club\StorageLocation;
use App\Support\ApiError;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class StorageLocationController extends Controller
{
    public function index(IndexStorageLocationRequest $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', StorageLocation::class);

        $filters = $request->validated();
        $sort = $filters['sort'] ?? 'name';
        $column = ltrim($sort, '-');
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';

        $locations = StorageLocation::query()
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('location_code', 'like', "%{$search}%");
                });
            })
            ->when(array_key_exists('is_active', $filters), fn ($query) => $query->where('is_active', $filters['is_active']))
            ->orderBy($column, $direction)
            ->orderBy('id')
            ->paginate($filters['per_page'] ?? 20);

        return StorageLocationResource::collection($locations);
    }

    public function store(StoreStorageLocationRequest $request): JsonResponse
    {
        Gate::authorize('create', StorageLocation::class);

        $storageLocation = StorageLocation::create($request->validated());

        return StorageLocationResource::make($storageLocation->refresh())->response()->setStatusCode(201);
    }

    public function show(StorageLocation $storageLocation): StorageLocationResource
    {
        Gate::authorize('view', $storageLocation);

        return StorageLocationResource::make($storageLocation);
    }

    public function update(UpdateStorageLocationRequest $request, StorageLocation $storageLocation): StorageLocationResource
    {
        Gate::authorize('update', $storageLocation);

        $storageLocation->update($request->validated());

        return StorageLocationResource::make($storageLocation->refresh());
    }

    public function destroy(StorageLocation $storageLocation): Response|JsonResponse
    {
        Gate::authorize('delete', $storageLocation);

        if ($storageLocation->balances()->where('quantity', '>', 0)->exists()) {
            return ApiError::conflict('resource_in_use');
        }

        $storageLocation->delete();

        return response()->noContent();
    }
}
