<?php

namespace App\Http\Resources\Admin;

use App\Models\Club\Permission;
use App\Models\Club\Role;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Rol del club. Lo usan los endpoints del club y los del admin de plataforma. @mixin Role */
class ClubRoleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'description' => $this->description,
            /** Estándar: no se edita ni se borra, se duplica. */
            'is_system' => $this->is_system,
            /** Cuántos usuarios del club tienen el rol. */
            'users_count' => $this->whenCounted('clubUsers'),
            /**
             * Permisos del rol con su alcance: all (todo el club) u own (sus categorías).
             *
             * @var list<array{name: string, scope: string}>
             */
            'permissions' => $this->whenLoaded('permissions', fn () => $this->permissions
                ->sortBy('name')
                ->map(fn (Permission $permission) => ['name' => $permission->name, 'scope' => $permission->pivot->scope])
                ->values()
                ->all()),
        ];
    }
}
