<?php

namespace App\Http\Resources\Club;

use App\Club\Callups\SquadOption;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SquadOption */
class SquadOptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'player_id' => $this->player_id,
            'player_name' => $this->player_name,
            'shirt_number' => $this->shirt_number,
            /** Habilitado para jugar: solo estos se pueden convocar. */
            'is_eligible' => $this->is_eligible,
            /**
             * Requisitos obligatorios que le faltan.
             *
             * @var list<string>
             */
            'missing' => $this->missing,
            'called_up' => $this->called_up,
        ];
    }
}
