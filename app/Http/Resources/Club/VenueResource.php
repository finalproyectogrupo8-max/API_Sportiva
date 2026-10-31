<?php

namespace App\Http\Resources\Club;

use App\Models\Club\Venue;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Venue */
class VenueResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'address' => $this->address,
            'latitude' => $this->latitude !== null ? (float) $this->latitude : null,
            'longitude' => $this->longitude !== null ? (float) $this->longitude : null,
            /** Link de Google Maps: por coordenadas si las hay, si no por dirección. */
            'maps_url' => $this->mapsUrl(),
            'is_active' => $this->is_active,
            'notes' => $this->notes,
        ];
    }
}
