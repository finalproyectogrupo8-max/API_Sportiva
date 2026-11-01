<?php

namespace App\Http\Controllers\Club;

use App\Http\Controllers\Controller;
use App\Http\Requests\Club\IndexMaterialRequest;
use App\Http\Requests\Club\StoreMaterialRequest;
use App\Http\Requests\Club\UpdateMaterialRequest;
use App\Http\Resources\Club\MaterialResource;
use App\Models\Club\Material;
use App\Support\ApiError;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class MaterialController extends Controller
{
    public function index(IndexMaterialRequest $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Material::class);

        $filters = $request->validated();
        $sort = $filters['sort'] ?? 'name';
        $column = ltrim($sort, '-');
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';

        $materials = Material::query()
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where('name', 'like', "%{$search}%"))
            ->when($filters['unit'] ?? null, fn ($query, $unit) => $query->where('unit', $unit))
            ->when(array_key_exists('is_active', $filters), fn ($query) => $query->where('is_active', $filters['is_active']))
            ->orderBy($column, $direction)
            ->orderBy('id')
            ->paginate($filters['per_page'] ?? 20);

        return MaterialResource::collection($materials);
    }

    public function store(StoreMaterialRequest $request): JsonResponse
    {
        Gate::authorize('create', Material::class);

        $material = Material::create($request->validated());

        return MaterialResource::make($material->refresh())->response()->setStatusCode(201);
    }

    public function show(Material $material): MaterialResource
    {
        Gate::authorize('view', $material);

        return MaterialResource::make($material);
    }

    public function update(UpdateMaterialRequest $request, Material $material): MaterialResource
    {
        Gate::authorize('update', $material);

        $material->update($request->validated());

        return MaterialResource::make($material->refresh());
    }

    public function destroy(Material $material): Response|JsonResponse
    {
        Gate::authorize('delete', $material);

        if ($material->balances()->where('quantity', '>', 0)->exists()) {
            return ApiError::conflict('resource_in_use');
        }

        $material->delete();

        return response()->noContent();
    }
}
