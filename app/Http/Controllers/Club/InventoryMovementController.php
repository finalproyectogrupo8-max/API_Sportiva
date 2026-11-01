<?php

namespace App\Http\Controllers\Club;

use App\Http\Controllers\Club\Concerns\ChecksClubScope;
use App\Http\Controllers\Controller;
use App\Http\Requests\Club\IndexInventoryMovementRequest;
use App\Http\Requests\Club\StoreInventoryMovementRequest;
use App\Http\Resources\Club\InventoryMovementResource;
use App\Models\Club\ClubUser;
use App\Models\Club\InventoryMovement;
use App\Services\Club\InventoryMovementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class InventoryMovementController extends Controller
{
    use ChecksClubScope;

    public function index(IndexInventoryMovementRequest $request): AnonymousResourceCollection
    {
        $this->authorizeClub('inventory.view');

        $filters = $request->validated();
        $sort = $filters['sort'] ?? '-created_at';
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';

        $movements = InventoryMovement::query()
            ->with(['material', 'location'])
            ->when($filters['material_id'] ?? null, fn ($q, $matId) => $q->where('material_id', $matId))
            ->when($filters['location_id'] ?? null, fn ($q, $locId) => $q->where('location_id', $locId))
            ->when($filters['movement_type'] ?? null, fn ($q, $type) => $q->where('movement_type', $type))
            ->when($filters['transfer_group_id'] ?? null, fn ($q, $groupId) => $q->where('transfer_group_id', $groupId))
            ->orderBy('created_at', $direction)
            ->orderBy('id', $direction)
            ->paginate($filters['per_page'] ?? 20);

        return InventoryMovementResource::collection($movements);
    }

    public function store(StoreInventoryMovementRequest $request, InventoryMovementService $service): JsonResponse
    {
        $this->authorizeClub('inventory.adjust');

        $data = $request->validated();
        $clubUserId = $request->user()->clubAccess()->clubUser?->id
            ?? ClubUser::where('global_user_id', $request->user()->id)->value('id');

        if (! $clubUserId) {
            $clubUser = ClubUser::create([
                'global_user_id' => $request->user()->id,
                'display_name' => $request->user()->name,
                'status' => 'active',
            ]);
            $clubUserId = $clubUser->id;
        }

        $data['performed_by'] = $clubUserId;
        $movement = $service->recordMovement($data);

        return InventoryMovementResource::make($movement->load(['material', 'location']))
            ->response()
            ->setStatusCode(201);
    }
}
