<?php

namespace App\Http\Middleware;

use App\Models\PersonalAccessToken;
use App\Models\Tenant;
use App\Support\ApiError;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $token = $user?->currentAccessToken();
        $tenantId = $token instanceof PersonalAccessToken ? $token->tenant_id : null;

        if (! $tenantId) {
            return ApiError::forbidden('tenant_required');
        }

        $tenant = Tenant::find($tenantId);

        $hasMembership = $tenant && $user->tenantMemberships()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->exists();

        if (! $hasMembership) {
            return ApiError::forbidden('membership_inactive');
        }

        if ($tenant->status !== 'active') {
            return ApiError::forbidden('tenant_inactive');
        }

        $request->attributes->set('tenant', $tenant);

        return $next($request);
    }
}
