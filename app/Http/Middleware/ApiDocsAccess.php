<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Acceso a la documentación de la API (/api/docs y /api/docs/openapi.json).
 *
 * - local: abierta.
 * - staging: HTTP Basic con config('api-docs.*'). Sin credenciales configuradas, 404.
 * - cualquier otro entorno (production, testing, desconocidos): 404.
 *
 * Es la segunda capa: en producción las rutas ni siquiera se registran (routes/api.php).
 * Falla cerrada: ante cualquier duda, la documentación no existe.
 */
class ApiDocsAccess
{
    public const CSP_NONCE_ATTRIBUTE = 'api_docs_csp_nonce';

    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('staging')) {
            $username = config('api-docs.username');
            $password = config('api-docs.password');

            if (! $this->isConfigured($username) || ! $this->isConfigured($password)) {
                abort(404);
            }

            if (! $this->hasValidCredentials($request, $username, $password)) {
                return response()->json(['message' => 'Unauthenticated.'], 401, [
                    'WWW-Authenticate' => 'Basic realm="API Sportiva docs", charset="UTF-8"',
                ]);
            }
        } elseif (! app()->environment('local')) {
            abort(404);
        }

        // Nonce para el único script inline de la vista de Swagger UI (ver la CSP de abajo).
        $nonce = base64_encode(random_bytes(16));
        $request->attributes->set(self::CSP_NONCE_ATTRIBUTE, $nonce);

        $response = $next($request);

        $response->headers->set('X-Robots-Tag', 'noindex');
        $response->headers->set('Cache-Control', 'no-store');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'no-referrer');

        if (str_starts_with((string) $response->headers->get('Content-Type'), 'text/html')) {
            $response->headers->set('Content-Security-Policy', $this->contentSecurityPolicy($nonce));
        }

        return $response;
    }

    /**
     * Solo se permiten los archivos de la versión fijada de Swagger UI y el script inline con
     * nonce. SRI ya bloquea un bundle alterado; la CSP limita lo que un bundle vulnerable podría
     * hacer (por ejemplo, mandar datos a otro origen).
     */
    private function contentSecurityPolicy(string $nonce): string
    {
        $assets = 'https://cdn.jsdelivr.net/npm/swagger-ui-dist@'.config('scramble.renderers.swagger.version').'/';

        return implode('; ', [
            "default-src 'none'",
            "script-src {$assets} 'nonce-{$nonce}'",
            // Swagger UI aplica estilos inline desde JS.
            "style-src {$assets} 'unsafe-inline'",
            "img-src 'self' data:",
            "font-src 'self' data:",
            "connect-src 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
            "base-uri 'none'",
            "object-src 'none'",
        ]);
    }

    private function isConfigured(mixed $value): bool
    {
        return is_string($value) && $value !== '';
    }

    private function hasValidCredentials(Request $request, string $username, string $password): bool
    {
        $givenUsername = $request->getUser();
        $givenPassword = $request->getPassword();

        if ($givenUsername === null || $givenPassword === null) {
            return false;
        }

        // Se comparan hashes de largo fijo: hash_equals es de tiempo constante pero corta antes si
        // los largos difieren, y eso filtraría el largo de las credenciales. Sin cortocircuito.
        $usernameMatches = hash_equals(hash('sha256', $username), hash('sha256', $givenUsername));
        $passwordMatches = hash_equals(hash('sha256', $password), hash('sha256', $givenPassword));

        return $usernameMatches && $passwordMatches;
    }
}
