<?php

namespace App\Http\Resources\Club;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RepairRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'facility_id' => $this->facility_id,
            'component_id' => $this->component_id,
            'reported_by' => $this->reported_by,
            'assigned_to' => $this->assigned_to,
            'title' => $this->title,
            'description' => $this->description,
            'priority' => $this->priority,
            'status' => $this->status,
            'reported_at' => $this->reported_at?->toIso8601String(),
            'resolved_at' => $this->resolved_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'facility' => FacilityResource::make($this->whenLoaded('facility')),
            'component' => FacilityComponentResource::make($this->whenLoaded('component')),
            'maintenance_record' => MaintenanceRecordResource::make($this->whenLoaded('maintenanceRecord')),
        ];
    }
}
