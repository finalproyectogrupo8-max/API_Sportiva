<?php

namespace App\Http\Resources\Auth;

use App\Club\Access\ClubAccess;
use App\Http\Resources\TenantSummaryResource;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Laravel\Sanctum\NewAccessToken;

/** @mixin NewAccessToken */
class SelectTenantResource extends JsonResource
{
    public static $wrap = null;

    public function __construct(NewAccessToken $token, private Tenant $tenant, private ClubAccess $access)
    {
        parent::__construct($token);
    }

    public function toArray(Request $request): array
    {
        return [
            'token' => $this->plainTextToken,
            'expires_at' => $this->accessToken->expires_at->toIso8601String(),
            'tenant' => new TenantSummaryResource($this->tenant),
            'profile' => $this->access->clubUser ? new ClubProfileResource($this->access->clubUser) : null,
            /** @var list<string> */
            'permissions' => $this->access->permissionNames(),
            /** @var array<string, string> */
            'permission_scopes' => (object) $this->access->scopes(),
            /**
             * Deportes asignados al usuario en el club.
             *
             * @var list<array{id: int, name: string}>
             */
            'sports' => $this->access->assignedSports(),
        ];
    }
}
