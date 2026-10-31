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
            /** URL del escudo en la API (`GET /api/public/clubs/{slug}/logo`), o null si no tiene. */
            'logo_url' => $this->logo_path ? url("/api/public/clubs/{$this->slug}/logo").'?v='.substr(md5($this->logo_path), 0, 8) : null,
        ];
    }
}
