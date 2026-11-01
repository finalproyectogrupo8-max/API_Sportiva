<?php

namespace App\Http\Resources\Club;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApparelOrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'player_id' => $this->player_id,
            'player' => PlayerResource::make($this->whenLoaded('player')),
            'apparel_item_id' => $this->apparel_item_id,
            'apparel_item' => ApparelItemResource::make($this->whenLoaded('apparelItem')),
            'size' => $this->size,
            'shirt_number' => $this->shirt_number,
            'alias' => $this->alias,
            'quantity' => (int) $this->quantity,
            'delivered_quantity' => (int) $this->delivered_quantity,
            'notes' => $this->notes,
        ];
    }
}
