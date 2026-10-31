<?php

namespace App\Http\Resources\Admin;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Tenant */
class TenantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'short_name' => $this->short_name,
            'city' => $this->city,
            'founded_year' => $this->founded_year,
            /** Hexadecimal (#rrggbb). Acento del club: escudo y chips. */
            'primary_color' => $this->primary_color,
            'database_name' => $this->database_name,
            'status' => $this->status,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
