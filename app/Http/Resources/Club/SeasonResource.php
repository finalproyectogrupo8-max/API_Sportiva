<?php

namespace App\Http\Resources\Club;

use App\Models\Club\Season;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Season */
class SeasonResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            // null: temporada de todo el club.
            'sport_id' => $this->sport_id,
            'sport' => $this->whenLoaded('sport', fn () => $this->sport ? ['id' => $this->sport->id, 'name' => $this->sport->name] : null),
            'name' => $this->name,
            'start_date' => $this->start_date->toDateString(),
            'end_date' => $this->end_date->toDateString(),
            'status' => $this->status,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
