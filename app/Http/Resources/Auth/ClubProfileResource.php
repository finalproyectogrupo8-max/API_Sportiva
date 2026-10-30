<?php

namespace App\Http\Resources\Auth;

use App\Models\Club\ClubUser;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ClubUser */
class ClubProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'display_name' => $this->display_name,
            'status' => $this->status,
            'roles' => $this->roles
                ->sortBy('name')
                ->map(fn ($role) => ['slug' => $role->slug, 'name' => $role->name])
                ->values()
                ->all(),
        ];
    }
}
