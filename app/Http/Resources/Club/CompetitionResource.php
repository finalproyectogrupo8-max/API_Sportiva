<?php

namespace App\Http\Resources\Club;

use App\Models\Club\Competition;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Competition */
class CompetitionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sport_id' => $this->sport_id,
            'season_id' => $this->season_id,
            'name' => $this->name,
            'organizer' => $this->organizer,
            'registration_deadline' => $this->registration_deadline?->toDateString(),
            /** Días que faltan para el cierre de inscripción (negativo si ya pasó). */
            'days_to_deadline' => $this->registration_deadline ? (int) today()->diffInDays($this->registration_deadline, false) : null,
            'categories' => CompetitionCategoryResource::collection($this->whenLoaded('categories')),
            'requirements' => CompetitionRequirementResource::collection($this->whenLoaded('requirements')),
        ];
    }
}
