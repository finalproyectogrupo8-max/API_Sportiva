<?php

namespace App\Http\Controllers\Club;

use App\Http\Controllers\Controller;
use App\Http\Requests\Club\IndexApparelItemRequest;
use App\Http\Requests\Club\StoreApparelItemRequest;
use App\Http\Requests\Club\UpdateApparelItemRequest;
use App\Http\Resources\Club\ApparelItemResource;
use App\Models\Club\ApparelItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class ApparelItemController extends Controller
{
    public function index(IndexApparelItemRequest $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', ApparelItem::class);

        $filters = $request->validated();
        $sort = $filters['sort'] ?? 'name';
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $column = ltrim($sort, '-');

        $items = ApparelItem::query()
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where('name', 'like', "%{$search}%");
            })
            ->when(isset($filters['is_active']), function ($q) use ($filters) {
                $q->where('is_active', (bool) $filters['is_active']);
            })
            ->orderBy($column, $direction)
            ->paginate($filters['per_page'] ?? 20);

        return ApparelItemResource::collection($items);
    }

    public function store(StoreApparelItemRequest $request): JsonResponse
    {
        Gate::authorize('create', ApparelItem::class);

        $item = ApparelItem::create($request->validated());

        return ApparelItemResource::make($item)
            ->response()
            ->setStatusCode(201);
    }

    public function show(ApparelItem $apparelItem): ApparelItemResource
    {
        Gate::authorize('view', $apparelItem);

        return ApparelItemResource::make($apparelItem);
    }

    public function update(UpdateApparelItemRequest $request, ApparelItem $apparelItem): ApparelItemResource
    {
        Gate::authorize('update', $apparelItem);

        $apparelItem->update($request->validated());

        return ApparelItemResource::make($apparelItem->refresh());
    }

    public function destroy(ApparelItem $apparelItem): Response
    {
        Gate::authorize('delete', $apparelItem);

        $apparelItem->delete();

        return response()->noContent();
    }
}
