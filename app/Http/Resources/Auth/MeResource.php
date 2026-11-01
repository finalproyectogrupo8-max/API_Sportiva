<?php

namespace App\Http\Resources\Auth;

use App\Club\Access\ClubAccess;
use App\Http\Resources\TenantSummaryResource;
use App\Http\Resources\UserResource;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class MeResource extends JsonResource
{
    public static $wrap = null;

    public function __construct(User $user, private Tenant $tenant, private ClubAccess $access)
    {
        parent::__construct($user);
    }

    public function toArray(Request $request): array
    {
        return [
            'user' => new UserResource($this->resource),
            'tenant' => new TenantSummaryResource($this->tenant),
            'profile' => $this->access->clubUser ? new ClubProfileResource($this->access->clubUser) : null,
            /** @var list<string> */
            'permissions' => $this->access->permissionNames(),
            /**
             * Alcance de cada permiso: all (todo el club), sport (sus deportes) u own (sus categorías).
             *
             * @var array<string, string>
             */
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
