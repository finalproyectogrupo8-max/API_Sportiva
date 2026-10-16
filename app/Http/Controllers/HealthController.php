<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Estado de la API y de su base central.
 *
 * El cuerpo es siempre fijo: nunca incluye hostnames, nombres de bases, versiones ni
 * mensajes de excepción, sin importar APP_DEBUG. El detalle de un fallo va solo al log.
 */
class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $database = $this->checkDatabase();
        $healthy = $database === 'ok';

        return response()
            ->json([
                'status' => $healthy ? 'ok' : 'error',
                'checks' => ['database' => $database],
            ], $healthy ? 200 : 503)
            ->header('Cache-Control', 'no-store');
    }

    private function checkDatabase(): string
    {
        try {
            DB::connection()->select('select 1');

            return 'ok';
        } catch (Throwable $e) {
            Log::error('Health check: la base central no responde.', ['exception' => $e]);

            return 'error';
        }
    }
}
