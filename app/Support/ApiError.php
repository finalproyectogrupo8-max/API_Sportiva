<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

class ApiError
{
    private const MESSAGES = [
        'tenant_required' => 'Tenés que elegir un club.',
        'membership_inactive' => 'No tenés acceso a este club.',
        'tenant_inactive' => 'El club no está activo.',
    ];

    public static function forbidden(string $code): JsonResponse
    {
        return response()->json([
            'message' => self::MESSAGES[$code] ?? 'No autorizado.',
            'code' => $code,
        ], 403);
    }
}
