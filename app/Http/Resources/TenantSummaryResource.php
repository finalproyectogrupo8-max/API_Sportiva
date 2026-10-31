<?php

namespace App\Http\Resources;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Tenant */
class TenantSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'short_name' => $this->short_name,
            'city' => $this->city,
            'founded_year' => $this->founded_year,
            /** Hexadecimal (#rrggbb). Acento del club: escudo y chips. */
            'primary_color' => $this->primary_color,
        ];
    }
}
