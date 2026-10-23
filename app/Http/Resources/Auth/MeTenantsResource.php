<?php

namespace App\Http\Resources\Auth;

use App\Http\Resources\TenantSummaryResource;
use App\Http\Resources\UserResource;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/** @mixin User */
class MeTenantsResource extends JsonResource
{
    public static $wrap = null;

    /** @param Collection<int, Tenant> $tenants */
    public function __construct(User $user, private Collection $tenants)
    {
        parent::__construct($user);
    }

    public function toArray(Request $request): array
    {
        // `user` deja que el front sepa quién es el usuario con cualquier token (pre-tenant,
        // de club o de plataforma): /me solo acepta tokens de club.
        return [
            'user' => new UserResource($this->resource),
            'tenants' => TenantSummaryResource::collection($this->tenants),
        ];
    }
}
