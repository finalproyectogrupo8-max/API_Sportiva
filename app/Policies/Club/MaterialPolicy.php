<?php

namespace App\Policies\Club;

use App\Models\Club\Material;
use App\Models\User;

class MaterialPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasClubPermission('materials.view');
    }

    public function view(User $user, Material $material): bool
    {
        return $user->hasClubPermission('materials.view');
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
