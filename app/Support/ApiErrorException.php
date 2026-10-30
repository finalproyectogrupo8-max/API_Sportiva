<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;
use RuntimeException;

/**
 * Error de negocio con `code` propio (403 o 409), para lanzarlo desde Actions y servicios.
 * Laravel llama a render() y responde con el mismo formato que ApiError.
 */
class ApiErrorException extends RuntimeException
{
    public function __construct(public readonly string $errorCode, public readonly int $status)
    {
        parent::__construct($errorCode);
    }

    public function render(): JsonResponse
    {
        return $this->status === 409
            ? ApiError::conflict($this->errorCode)
            : ApiError::forbidden($this->errorCode);
    }
}
