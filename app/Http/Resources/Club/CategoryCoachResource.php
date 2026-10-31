<?php

namespace App\Http\Resources\Club;

use App\Models\Club\CategoryCoach;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin CategoryCoach */
class CategoryCoachResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category_id' => $this->category_id,
            /** Nombre de la categoría, si se cargó. */
            'category_name' => $this->whenLoaded('category', fn () => $this->category->name),
            'coach_id' => $this->coach_id,
            /** Nombre y apellido del profe, si se cargó. */
            'coach_name' => $this->whenLoaded('coach', fn () => trim($this->coach->first_name.' '.$this->coach->last_name)),
            /** `head_coach`, `assistant` o `physical_trainer`. */
            'assignment_type' => $this->assignment_type,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
        ];
    }
}
