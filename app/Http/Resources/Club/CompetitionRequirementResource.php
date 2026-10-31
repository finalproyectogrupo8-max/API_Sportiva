<?php

namespace App\Http\Resources\Club;

use App\Models\Club\CompetitionRequirement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin CompetitionRequirement */
class CompetitionRequirementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            /** Meses de validez; null si no vence. */
            'validity_months' => $this->validity_months,
            'requires_file' => $this->requires_file,
            /** Solo se pide a menores de 18. */
            'minors_only' => $this->minors_only,
            'is_required' => $this->is_required,
            'sort' => $this->sort,
        ];
    }
}
