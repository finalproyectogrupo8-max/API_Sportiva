<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictToFrontendServer
{
    /**
     * Rutas exentas de restricción por IP/Secret (tienen sus propios middlewares y políticas).
     */
    protected array $except = [
        'api/health',
        'api/docs',
        'api/docs/*',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        // 1. En local, testing, o si la restricción está explícitamente desactivada, permitimos todo.
        $restrictionEnabled = (bool) config('services.frontend.restriction_enabled', false);

        if (! $restrictionEnabled || app()->environment(['local', 'testing'])) {
            return $next($request);
        }

        // 2. Rutas públicas exentas (health check, Swagger docs)
        foreach ($this->except as $except) {
            if ($request->is($except)) {
                return $next($request);
            }
        }

        // 3. Validación por Secret Token (Header para BFF en Vercel sin IPs fijas)
        $bffSecret = (string) config('services.frontend.bff_secret');
        $incomingSecret = (string) ($request->header('X-Sportiva-BFF-Secret') ?: $request->header('X-Nextjs-Secret'));

        if ($bffSecret !== '' && hash_equals($bffSecret, $incomingSecret)) {
            return $next($request);
        }

        // 4. Validación por Whitelist de IPs (si el servidor de Next.js tiene IP fija)
        $allowedIps = (array) config('services.frontend.server_ips', []);
        if (! empty($allowedIps) && in_array($request->ip(), $allowedIps, true)) {
            return $next($request);
        }

        // 5. Validación por Origin / Referer del Frontend
        $frontendUrl = (string) config('services.frontend.url');
        $origin = (string) ($request->header('Origin') ?: $request->header('Referer'));
        if ($frontendUrl !== '' && $origin !== '' && str_starts_with($origin, $frontendUrl)) {
            return $next($request);
        }

        return response()->json([
            'code' => 'forbidden_origin',
            'message' => 'Acceso denegado: las peticiones deben originarse exclusivamente desde el servidor frontend (BFF).',
        ], 403);
    }
}
