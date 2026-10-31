<?php

namespace App\Http\Resources\Club;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MaintenanceRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'repair_request_id' => $this->repair_request_id,
            'facility_id' => $this->facility_id,
            'performed_by' => $this->performed_by,
            'performed_at' => $this->performed_at?->toIso8601String(),
            'description' => $this->description,
            'cost' => $this->cost !== null ? (float) $this->cost : null,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'facility' => FacilityResource::make($this->whenLoaded('facility')),
            'repair_request' => RepairRequestResource::make($this->whenLoaded('repairRequest')),
        ];
    }
}
