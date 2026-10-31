<?php

namespace App\Http\Resources\Club;

use App\Models\Club\GameMatch;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin GameMatch */
class MatchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category_id' => $this->category_id,
            /** Nombre de la categoría, si se cargó. */
            'category_name' => $this->whenLoaded('category', fn () => $this->category->name),
            'competition_id' => $this->competition_id,
            'opponent' => $this->opponent,
            'match_date' => $this->match_date->toIso8601String(),
            /** Hora de presentación. */
            'meet_at' => $this->meet_at?->toIso8601String(),
            /** Sede del club donde se juega (de local), si se eligió una. */
            'venue_id' => $this->venue_id,
            'venue' => $this->venue,
            'maps_url' => $this->maps_url,
            'is_home' => $this->is_home,
            /** `home` (titular) o `away` (alternativa). */
            'uniform' => $this->uniform,
            'uniform_color' => $this->uniform_color,
            'what_to_bring' => $this->what_to_bring,
            'our_score' => $this->our_score,
            'opponent_score' => $this->opponent_score,
            /** `scheduled`, `played`, `postponed` o `cancelled`. */
            'status' => $this->status,
            'notes' => $this->notes,
        ];
    }
}
