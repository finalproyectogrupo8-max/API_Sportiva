<?php

namespace App\Http\Resources\Club;

use App\Club\Eligibility\PlayerEligibility;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PlayerEligibility */
class PlayerEligibilityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'player_id' => $this->player_id,
            'player_name' => $this->player_name,
            'shirt_number' => $this->shirt_number,
            /** Tiene aprobados y vigentes todos los requisitos obligatorios que le aplican. */
            'is_eligible' => $this->is_eligible,
            /** Requisitos obligatorios que le faltan. */
            'missing' => $this->missing,
            'requirements' => RequirementStatusResource::collection($this->requirements),
        ];
    }
}
