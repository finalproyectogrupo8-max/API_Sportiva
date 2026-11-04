<?php

namespace App\Policies\Club;

use App\Models\Club\Material;
use App\Models\User;

class MaterialPolicy
{
    /** Quien puede pedir materiales necesita ver el catálogo para elegir qué pedir. */
    public function viewAny(User $user): bool
    {
        return $user->hasClubPermission('materials.view') || $user->hasClubPermission('material_orders.create');
    }

    public function view(User $user, Material $material): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasClubPermission('materials.create');
    }

    public function update(User $user, Material $material): bool
    {
        return $user->hasClubPermission('materials.update');
    }

    public function delete(User $user, Material $material): bool
    {
        return $user->hasClubPermission('materials.delete');
    }
}
