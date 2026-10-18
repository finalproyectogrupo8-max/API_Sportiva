<?php

namespace App\Tenancy;

use Illuminate\Http\JsonResponse;
use RuntimeException;

/**
 * tenants.database_name no cumple la convención club_<slug>. Es un error interno: se registra
 * con el tenant_id y el cliente recibe un 500 genérico, sin el nombre de la base.
 */
class InvalidTenantDatabaseName extends RuntimeException
{
    public function __construct(public readonly ?int $tenantId)
    {
        parent::__construct("El tenant [{$tenantId}] tiene un database_name inválido.");
    }

    /** Se agrega al log del error. */
    public function context(): array
    {
        return ['tenant_id' => $this->tenantId];
    }

    public function render(): JsonResponse
    {
        return response()->json(['message' => 'Server Error'], 500);
    }
}
