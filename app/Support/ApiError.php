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
        'club_user_disabled' => 'Tu usuario está deshabilitado en este club.',
        'tenant_not_failed' => 'Solo se pueden reintentar clubes en estado fallido.',
        'resource_in_use' => 'El recurso está en uso y no se puede eliminar.',
        'role_is_system' => 'Los roles estándar no se editan ni se borran: duplicalo para crear uno propio.',
        'role_in_use' => 'El rol está asignado a usuarios del club: sacáselo antes de borrarlo.',
        'last_admin' => 'El club tiene que tener al menos un Administrador del club activo.',
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
