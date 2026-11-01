<?php

namespace App\Http\Resources\Club;

use App\Club\Calendar\CalendarEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin CalendarEvent */
class CalendarEventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            /** `match` o `training`. */
            'type' => $this->type,
            'id' => $this->id,
            'category_id' => $this->category_id,
            'category_name' => $this->category_name,
            'title' => $this->title,
            'starts_at' => $this->starts_at,
            'ends_at' => $this->ends_at,
            'location' => $this->location,
            /** Solo partidos: `scheduled`, `played`, `postponed` o `cancelled`. */
            'status' => $this->status,
        ];
    }
}
