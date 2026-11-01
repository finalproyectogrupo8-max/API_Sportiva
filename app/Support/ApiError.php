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
        'insufficient_stock' => 'Stock insuficiente para realizar la operación.',
        'repair_already_resolved' => 'La reparación ya fue resuelta.',
        'repair_not_assigned' => 'La reparación debe estar asignada para poder resolverse.',
        'repair_cancelled' => 'La reparación está cancelada.',
        'invalid_status_transition' => 'La entidad no se puede modificar en su estado actual.',
        'order_not_draft' => 'Solo se pueden editar o eliminar pedidos en estado borrador.',
        'order_not_submitted' => 'El pedido debe estar enviado para poder ser aprobado o rechazado.',
        'order_already_approved' => 'El pedido ya fue aprobado.',
        'order_already_delivered' => 'El pedido ya fue entregado completamente.',
        'order_rejected' => 'El pedido fue rechazado y no admite más cambios.',
        'invalid_order_transition' => 'Transición de estado no permitida para el pedido.',
        'rejection_reason_required' => 'El motivo de rechazo es obligatorio.',
        'exceeds_ordered_quantity' => 'La cantidad entregada supera la cantidad pedida.',
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
