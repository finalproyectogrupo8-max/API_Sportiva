<?php

namespace App\Http\Middleware;

use App\Models\PersonalAccessToken;
use App\Models\Tenant;
use App\Support\ApiError;
use App\Tenancy\TenantDatabase;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantContext
{
    public function __construct(private TenantDatabase $tenantDatabase) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->is_active) {
            return response()->json(['message' => 'Usuario desactivado.'], 401);
        }

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

        // La base sale del tenant del token, nunca del request. La respuesta (incluidos los
        // Resources) se arma dentro de $next, así que al desconectar ya no se consulta la base.
        $this->tenantDatabase->connect($tenant);

        try {
            return $next($request);
        } finally {
            $this->tenantDatabase->disconnect();
        }
    }
}
