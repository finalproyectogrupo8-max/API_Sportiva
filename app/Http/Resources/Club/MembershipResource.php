<?php

namespace App\Http\Resources\Club;

use App\Models\Club\PlayerCategoryMembership;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PlayerCategoryMembership */
class MembershipResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'player_id' => $this->player_id,
            'category_id' => $this->category_id,
            /** Nombre de la categoría, si se cargó. */
            'category_name' => $this->whenLoaded('category', fn () => $this->category->name),
            /** Nombre y apellido del jugador, si se cargó. */
            'player_name' => $this->whenLoaded('player', fn () => trim($this->player->first_name.' '.$this->player->last_name)),
            'start_date' => $this->start_date->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'shirt_number' => $this->shirt_number,
            /** `active` mientras juega en la categoría; `ended` cuando se cerró. */
            'status' => $this->status,
        ];
    }
}
