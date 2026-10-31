<?php

namespace App\Http\Resources\Club;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryBalanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isBelowMinimum = null;
        if ($this->relationLoaded('material') && $this->material !== null) {
            $isBelowMinimum = (float) $this->quantity < (float) $this->material->minimum_stock;
        }

        return [
            'id' => $this->id,
            'material_id' => $this->material_id,
            'location_id' => $this->location_id,
            'quantity' => (float) $this->quantity,
            'is_below_minimum' => $isBelowMinimum,
            'updated_at' => $this->updated_at?->toIso8601String(),
            'material' => MaterialResource::make($this->whenLoaded('material')),
            'location' => StorageLocationResource::make($this->whenLoaded('location')),
        ];
    }
}
