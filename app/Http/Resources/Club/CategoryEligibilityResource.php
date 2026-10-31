<?php

namespace App\Http\Resources\Club;

use App\Club\Eligibility\CategoryEligibility;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin CategoryEligibility */
class CategoryEligibilityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'category_id' => $this->category_id,
            'category_name' => $this->category_name,
            /** Jugadores activos en la categoría. */
            'players' => $this->players,
            /** Cuántos están habilitados. */
            'eligible' => $this->eligible,
            /** Cierre de inscripción más próximo (todavía no pasado). */
            'registration_deadline' => $this->registration_deadline,
            'days_to_deadline' => $this->days_to_deadline,
            /** Requisitos aprobados que vencen en los próximos 30 días. */
            'expiring_soon' => $this->expiring_soon,
        ];
    }
}
