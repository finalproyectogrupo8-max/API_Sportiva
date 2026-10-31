<?php

namespace App\Http\Resources\Club;

use App\Club\Eligibility\RequirementStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin RequirementStatus */
class RequirementStatusResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'requirement_id' => $this->requirement_id,
            'name' => $this->name,
            'competition_id' => $this->competition_id,
            'competition_name' => $this->competition_name,
            /** `pending`, `submitted`, `approved`, `rejected`, `expired` o `not_applicable`. */
            'status' => $this->status,
            'is_required' => $this->is_required,
            'requires_file' => $this->requires_file,
            'expires_at' => $this->expires_at,
            /** Aprobado y vence en los próximos 30 días. */
            'expiring_soon' => $this->expiring_soon,
            /** Documento adjunto (`GET /api/documents/{id}/download`). */
            'document_id' => $this->document_id,
            'rejection_reason' => $this->rejection_reason,
        ];
    }
}
