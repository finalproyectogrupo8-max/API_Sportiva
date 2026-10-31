<?php

namespace App\Http\Controllers\Club;

use App\Http\Controllers\Club\Concerns\ChecksClubScope;
use App\Http\Controllers\Controller;
use App\Http\Requests\Club\StoreInventoryTransferRequest;
use App\Http\Resources\Club\InventoryTransferResource;
use App\Models\Club\ClubUser;
use App\Models\Club\InventoryBalance;
use App\Models\Club\InventoryMovement;
use App\Support\ApiError;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InventoryTransferController extends Controller
{
    use ChecksClubScope;

    public function store(StoreInventoryTransferRequest $request): JsonResponse
    {
        $this->authorizeClub('inventory.transfer');

        $data = $request->validated();
        $materialId = (int) $data['material_id'];
        $fromLocationId = (int) $data['from_location_id'];
        $toLocationId = (int) $data['to_location_id'];
        $quantity = (float) $data['quantity'];
        $transferGroupId = (string) Str::uuid();

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

        $result = DB::connection('tenant')->transaction(function () use (
            $materialId,
            $fromLocationId,
            $toLocationId,
            $quantity,
            $transferGroupId,
            $clubUserId,
            $data
        ) {
            // Consistent lock order prevents deadlocks between concurrent transfers
            $firstLoc = min($fromLocationId, $toLocationId);
            $secondLoc = max($fromLocationId, $toLocationId);

            $firstBalance = InventoryBalance::where('material_id', $materialId)
                ->where('location_id', $firstLoc)
                ->lockForUpdate()
                ->first();

            $secondBalance = InventoryBalance::where('material_id', $materialId)
                ->where('location_id', $secondLoc)
                ->lockForUpdate()
                ->first();

            $fromBalance = ($fromLocationId === $firstLoc) ? $firstBalance : $secondBalance;
            $toBalance = ($toLocationId === $firstLoc) ? $firstBalance : $secondBalance;

            if (! $fromBalance || (float) $fromBalance->quantity < $quantity) {
                abort(ApiError::conflict('insufficient_stock'));
            }

            // Deduct from origin
            $fromBalance->quantity = (float) $fromBalance->quantity - $quantity;
            $fromBalance->updated_at = now();
            $fromBalance->save();

            // Add to destination
            if ($toBalance) {
                $toBalance->quantity = (float) $toBalance->quantity + $quantity;
                $toBalance->updated_at = now();
                $toBalance->save();
            } else {
                $toBalance = InventoryBalance::create([
                    'material_id' => $materialId,
                    'location_id' => $toLocationId,
                    'quantity' => $quantity,
                    'updated_at' => now(),
                ]);
            }

            $outMovement = InventoryMovement::create([
                'material_id' => $materialId,
                'location_id' => $fromLocationId,
                'movement_type' => 'transfer_out',
                'quantity' => $quantity,
                'transfer_group_id' => $transferGroupId,
                'performed_by' => $clubUserId,
                'reason' => $data['reason'] ?? null,
                'reference' => $data['reference'] ?? null,
                'created_at' => now(),
            ]);

            $inMovement = InventoryMovement::create([
                'material_id' => $materialId,
                'location_id' => $toLocationId,
                'movement_type' => 'transfer_in',
                'quantity' => $quantity,
                'transfer_group_id' => $transferGroupId,
                'performed_by' => $clubUserId,
                'reason' => $data['reason'] ?? null,
                'reference' => $data['reference'] ?? null,
                'created_at' => now(),
            ]);

            return [
                'transfer_group_id' => $transferGroupId,
                'material_id' => $materialId,
                'from_location_id' => $fromLocationId,
                'to_location_id' => $toLocationId,
                'quantity' => $quantity,
                'reason' => $data['reason'] ?? null,
                'reference' => $data['reference'] ?? null,
                'movements' => [$outMovement->load(['material', 'location']), $inMovement->load(['material', 'location'])],
            ];
        });

        return InventoryTransferResource::make($result)
            ->response()
            ->setStatusCode(201);
    }
}
