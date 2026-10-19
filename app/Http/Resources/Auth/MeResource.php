<?php

namespace App\Http\Resources\Auth;

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

    public function __construct(User $user, private Tenant $tenant)
    {
        parent::__construct($user);
    }

    public function toArray(Request $request): array
    {
        return [
            'user' => new UserResource($this->resource),
            'tenant' => new TenantSummaryResource($this->tenant),
            'profile' => null,
            'permissions' => [],
        ];
    }
}
