<?php

namespace App\Http\Resources\Club;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MaterialOrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'material_id' => $this->material_id,
            'material' => MaterialResource::make($this->whenLoaded('material')),
            'quantity' => (float) $this->quantity,
            'delivered_quantity' => (float) $this->delivered_quantity,
            'notes' => $this->notes,
        ];
    }
}
