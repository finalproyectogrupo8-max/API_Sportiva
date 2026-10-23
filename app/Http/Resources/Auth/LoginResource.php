<?php

namespace App\Http\Resources\Auth;

use App\Http\Resources\TenantSummaryResource;
use App\Http\Resources\UserResource;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Laravel\Sanctum\NewAccessToken;

/** @mixin NewAccessToken */
class LoginResource extends JsonResource
{
    public static $wrap = null;

    /** @param Collection<int, Tenant> $tenants */
    public function __construct(
        NewAccessToken $token,
        private User $user,
        private Collection $tenants,
        private ?NewAccessToken $platformToken = null,
    ) {
        parent::__construct($token);
    }

    public function toArray(Request $request): array
    {
        return [
            'token' => $this->plainTextToken,
            'expires_at' => $this->accessToken->expires_at->toIso8601String(),
            'user' => new UserResource($this->user),
            'tenants' => TenantSummaryResource::collection($this->tenants),
            'platform_token' => $this->when(
                $this->platformToken !== null,
                fn () => $this->platformToken->plainTextToken,
            ),
            'platform_expires_at' => $this->when(
                $this->platformToken !== null,
                fn () => $this->platformToken->accessToken->expires_at?->toIso8601String(),
            ),
        ];
    }
}
