<?php

namespace App\Http\Resources\Club;

use App\Models\Club\Training;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Training */
class TrainingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category_id' => $this->category_id,
            /** Nombre de la categoría, si se cargó. */
            'category_name' => $this->whenLoaded('category', fn () => $this->category->name),
            'coach_id' => $this->coach_id,
            'starts_at' => $this->starts_at->toIso8601String(),
            'ends_at' => $this->ends_at->toIso8601String(),
            'location' => $this->location,
            'objectives' => $this->objectives,
            'notes' => $this->notes,
        ];
    }
}
