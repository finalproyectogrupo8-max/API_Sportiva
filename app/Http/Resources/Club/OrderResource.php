<?php

namespace App\Http\Resources\Club;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'type' => $this->type,
            'requested_by' => $this->requested_by,
            'category_id' => $this->category_id,
            'category' => CategoryResource::make($this->whenLoaded('category')),
            'status' => $this->status,
            'priority' => $this->priority,
            'description' => $this->description,
            'approved_by' => $this->approved_by,
            'approved_at' => $this->approved_at?->toIso8601String(),
            'delivered_at' => $this->delivered_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'apparel_order_items' => ApparelOrderItemResource::collection($this->whenLoaded('apparelOrderItems')),
            'material_order_items' => MaterialOrderItemResource::collection($this->whenLoaded('materialOrderItems')),
            'status_history' => OrderStatusHistoryResource::collection($this->whenLoaded('statusHistory')),
        ];
    }
}
