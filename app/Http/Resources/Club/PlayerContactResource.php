<?php

namespace App\Http\Resources\Club;

use App\Models\Club\PlayerContact;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PlayerContact */
class PlayerContactResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'player_id' => $this->player_id,
            'name' => $this->name,
            /** `mother`, `father`, `guardian`, `self` (el propio jugador) u `other`. */
            'relationship' => $this->relationship,
            'phone' => $this->phone,
            'email' => $this->email,
            /** El principal recibe los avisos de la convocatoria. */
            'is_primary' => $this->is_primary,
        ];
    }
}
