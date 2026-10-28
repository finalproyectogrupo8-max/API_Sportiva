<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role ?? ($this->is_platform_admin ? 'admin' : 'general'),
            // Solo para que el front decida si muestra /admin: la API verifica el permiso en cada request.
            'is_platform_admin' => (bool) $this->is_platform_admin,
        ];
    }
}
