<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

class ApiError
{
    private const MESSAGES = [
        'forbidden' => 'No tenés permiso para esta acción.',
        'tenant_required' => 'Tenés que elegir un club.',
        'membership_inactive' => 'No tenés acceso a este club.',
        'tenant_inactive' => 'El club no está activo.',
        'resource_in_use' => 'El recurso está en uso y no se puede eliminar.',
    ];

    public static function forbidden(string $code): JsonResponse
    {
        return response()->json([
            'message' => self::MESSAGES[$code] ?? 'No autorizado.',
            'code' => $code,
        ], 403);
    }

    public static function conflict(string $code): JsonResponse
    {
        return response()->json([
            'message' => self::MESSAGES[$code] ?? 'No se puede completar la operación.',
            'code' => $code,
        ], 409);
    }
}
