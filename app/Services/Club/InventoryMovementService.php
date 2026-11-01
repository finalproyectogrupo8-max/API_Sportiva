<?php

namespace App\Services\Club;

use App\Models\Club\InventoryBalance;
use App\Models\Club\InventoryMovement;
use App\Support\ApiError;
use Illuminate\Support\Facades\DB;

class InventoryMovementService
{
    /**
     * Registra un movimiento de inventario actualizando el saldo de forma atómica con lock.
     *
     * @param  array{
     *     material_id: int,
     *     location_id: int,
     *     movement_type: string,
     *     quantity: float|int|string,
     *     order_id?: int|null,
     *     performed_by: int,
     *     reason?: string|null,
     *     reference?: string|null,
     *     transfer_group_id?: string|null
     * }  $data
     */
    public function recordMovement(array $data): InventoryMovement
    {
        $materialId = (int) $data['material_id'];
        $locationId = (int) $data['location_id'];
        $movementType = $data['movement_type'];
        $quantity = (float) $data['quantity'];
        $clubUserId = (int) $data['performed_by'];

        return DB::connection('tenant')->transaction(function () use (
            $materialId,
            $locationId,
            $movementType,
            $quantity,
            $clubUserId,
            $data
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
                'transfer_in' => abs($quantity),
                'transfer_out' => -abs($quantity),
                default => 0.0,
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
                'transfer_group_id' => $data['transfer_group_id'] ?? null,
                'order_id' => $data['order_id'] ?? null,
                'performed_by' => $clubUserId,
                'reason' => $data['reason'] ?? null,
                'reference' => $data['reference'] ?? null,
                'created_at' => now(),
            ]);
        });
    }
}
