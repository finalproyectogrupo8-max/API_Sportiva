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

    public function __construct(User $user, private ?Tenant $tenant = null)
    {
        parent::__construct($user);
    }

    public function toArray(Request $request): array
    {
        $role = $this->resource->is_platform_admin ? 'platform_admin' : 'admin';

        return [
            'user' => new UserResource($this->resource),
            'tenant' => $this->tenant ? new TenantSummaryResource($this->tenant) : null,
            'profile' => [
                'id' => $this->resource->id,
                'role' => $role,
                'first_name' => $this->resource->name,
                'last_name' => '',
                'avatar_url' => null,
            ],
            'permissions' => [
                'manage-club',
                'view-dashboard',
                'manage-sports',
                'manage-seasons',
                'manage-categories',
                'manage-coaches',
                'manage-players',
                'view-reports',
            ],
        ];
    }
}
