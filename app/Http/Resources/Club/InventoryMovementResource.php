<?php

namespace App\Http\Resources\Club;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryMovementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'material_id' => $this->material_id,
            'location_id' => $this->location_id,
            'movement_type' => $this->movement_type,
            'quantity' => (float) $this->quantity,
            'transfer_group_id' => $this->transfer_group_id,
            'order_id' => $this->order_id,
            'performed_by' => $this->performed_by,
            'reason' => $this->reason,
            'reference' => $this->reference,
            'created_at' => $this->created_at?->toIso8601String(),
            'material' => MaterialResource::make($this->whenLoaded('material')),
            'location' => StorageLocationResource::make($this->whenLoaded('location')),
        ];
    }
}
