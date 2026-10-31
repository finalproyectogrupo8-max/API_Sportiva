<?php

namespace App\Http\Resources\Club;

use App\Models\Club\Player;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Player */
class PlayerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'birth_date' => $this->birth_date?->toDateString(),
            /** Edad en años cumplidos, calculada con la fecha de nacimiento. */
            'age' => $this->birth_date ? (int) $this->birth_date->age : null,
            'document_number' => $this->document_number,
            'registration_number' => $this->registration_number,
            'preferred_position' => $this->preferred_position,
            'dominant_side' => $this->dominant_side,
            'status' => $this->status,
            /** Id del documento con la foto (`GET /api/documents/{id}/download`), si se cargó y tiene. */
            'photo_document_id' => $this->whenLoaded('photo', fn () => $this->photo?->id),
            'notes' => $this->notes,
            /** Categorías en las que juega hoy, con su número. Solo si el controlador las cargó. */
            'memberships' => MembershipResource::collection($this->whenLoaded('activeMemberships')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
