<?php

namespace App\Http\Resources\Admin;

use App\Models\Club\Role;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Role */
class ClubRoleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'description' => $this->description,
            'is_system' => $this->is_system,
            /** Cuántos usuarios del club tienen el rol. */
            'users_count' => $this->whenCounted('clubUsers'),
        ];
    }
}
