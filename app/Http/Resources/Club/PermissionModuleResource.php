<?php

namespace App\Http\Resources\Club;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Un módulo de la grilla de permisos (PermissionCatalog::grid()): sus recursos y acciones.
 *
 * @property array{key: string, label: string, resources: list<array{key: string, label: string, actions: list<array{name: string, action: string, label: string, scopable: bool}>}>} $resource
 */
class PermissionModuleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            /** @var string */
            'key' => $this->resource['key'],
            /** @var string */
            'label' => $this->resource['label'],
            /**
             * Recursos del módulo. `scopable`: la acción admite "sus categorías".
             *
             * @var list<array{key: string, label: string, actions: list<array{name: string, action: string, label: string, scopable: bool}>}>
             */
            'resources' => $this->resource['resources'],
        ];
    }
}
