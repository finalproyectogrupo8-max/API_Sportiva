<?php

namespace App\Policies\Club;

use App\Models\Club\Role;
use App\Models\User;

class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasClubPermission('roles.view');
    }

    public function view(User $user, Role $role): bool
    {
        return $user->hasClubPermission('roles.view');
    }

    public function create(User $user): bool
    {
        return $user->hasClubPermission('roles.create');
    }

    public function update(User $user, Role $role): bool
    {
        return $user->hasClubPermission('roles.update');
    }

    public function delete(User $user, Role $role): bool
    {
        return $user->hasClubPermission('roles.delete');
    }
}
