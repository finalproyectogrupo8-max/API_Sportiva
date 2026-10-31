<?php

namespace App\Http\Controllers\Club;

use App\Http\Controllers\Club\Concerns\ChecksClubScope;
use App\Http\Controllers\Controller;
use App\Http\Requests\Club\IndexInventoryMovementRequest;
use App\Http\Requests\Club\StoreInventoryMovementRequest;
use App\Http\Resources\Club\InventoryMovementResource;
use App\Models\Club\ClubUser;
use App\Models\Club\InventoryBalance;
use App\Models\Club\InventoryMovement;
use App\Support\ApiError;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

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

    public function store(StoreInventoryMovementRequest $request): JsonResponse
    {
        $this->authorizeClub('inventory.adjust');

        $data = $request->validated();
        $materialId = (int) $data['material_id'];
        $locationId = (int) $data['location_id'];
        $movementType = $data['movement_type'];
        $quantity = (float) $data['quantity'];

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

        $movement = DB::connection('tenant')->transaction(function () use (
            $materialId,
            $locationId,
            $movementType,
            $quantity,
            $data,
            $clubUserId
        ) {
            $balance = InventoryBalance::where('material_id', $materialId)
                ->where('location_id', $locationId)
                ->lockForUpdate()
                ->first();

            $currentQuantity = $balance ? (float) $balance->quantity : 0.0;

            $delta = match ($movementType) {
                'in' => abs($quantity),
                'out' => -abs($quantity),
                'adjustment' => $quantity,
            };

            $newQuantity = $currentQuantity + $delta;

            if ($newQuantity < 0) {
                abort(ApiError::conflict('insufficient_stock'));
            }

            if ($balance) {
                $balance->quantity = $newQuantity;
                $balance->updated_at = now();
                $balance->save();
            } else {
                InventoryBalance::create([
                    'material_id' => $materialId,
                    'location_id' => $locationId,
                    'quantity' => $newQuantity,
                    'updated_at' => now(),
                ]);
            }

            $movementQuantity = ($movementType === 'adjustment') ? $quantity : abs($quantity);

            return InventoryMovement::create([
                'material_id' => $materialId,
                'location_id' => $locationId,
                'movement_type' => $movementType,
                'quantity' => $movementQuantity,
                'performed_by' => $clubUserId,
                'reason' => $data['reason'] ?? null,
                'reference' => $data['reference'] ?? null,
                'created_at' => now(),
            ]);
        });

        return InventoryMovementResource::make($movement->load(['material', 'location']))
            ->response()
            ->setStatusCode(201);
    }
}
