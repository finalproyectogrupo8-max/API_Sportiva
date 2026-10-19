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
        return [
            'token' => $this->plainTextToken,
            'expires_at' => $this->accessToken->expires_at->toIso8601String(),
            'tenant' => new TenantSummaryResource($this->tenant),
            'profile' => null,
            'permissions' => [],
        ];
    }
}
