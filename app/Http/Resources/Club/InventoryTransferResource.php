<?php

namespace App\Http\Resources\Club;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read array{transfer_group_id: string, material_id: int, from_location_id: int, to_location_id: int, quantity: float|int|string, reason: string|null, reference: string|null, movements: mixed} $resource
 */
class InventoryTransferResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'transfer_group_id' => $this->resource['transfer_group_id'],
            'material_id' => $this->resource['material_id'],
            'from_location_id' => $this->resource['from_location_id'],
            'to_location_id' => $this->resource['to_location_id'],
            'quantity' => (float) $this->resource['quantity'],
            'reason' => $this->resource['reason'] ?? null,
            'reference' => $this->resource['reference'] ?? null,
            'movements' => InventoryMovementResource::collection($this->resource['movements']),
        ];
    }
}
