<?php

namespace App\Http\Resources\Admin;

use App\Models\TenantMembership;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TenantMembership */
class TenantMembershipResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'tenant_id' => $this->tenant_id,
            'role' => $this->role,
            'is_active' => $this->is_active,
            'user' => UserResource::make($this->whenLoaded('user')),
            'tenant' => TenantResource::make($this->whenLoaded('tenant')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
