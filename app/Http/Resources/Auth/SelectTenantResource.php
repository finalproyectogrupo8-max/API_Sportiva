<?php

namespace App\Http\Resources\Auth;

use App\Http\Resources\TenantSummaryResource;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Laravel\Sanctum\NewAccessToken;

/** @mixin NewAccessToken */
class SelectTenantResource extends JsonResource
{
    public static $wrap = null;

    public function __construct(NewAccessToken $token, private Tenant $tenant)
    {
        parent::__construct($token);
    }

    public function toArray(Request $request): array
    {
        $user = $request->user();
        $role = $user?->is_platform_admin ? 'platform_admin' : 'admin';

        return [
            'token' => $this->plainTextToken,
            'expires_at' => $this->accessToken->expires_at->toIso8601String(),
            'tenant' => new TenantSummaryResource($this->tenant),
            'profile' => [
                'id' => $user?->id ?? $this->accessToken->tokenable_id,
                'role' => $role,
                'first_name' => $user?->name ?? 'Usuario',
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
