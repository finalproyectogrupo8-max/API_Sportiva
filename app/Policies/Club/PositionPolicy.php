<?php

namespace App\Policies\Club;

use App\Models\Club\Position;
use App\Models\User;

class PositionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasClubPermission('positions.view');
    }

    public function view(User $user, Position $position): bool
    {
        return $user->hasClubPermission('positions.view');
    }

    public function create(User $user): bool
    {
        return $user->hasClubPermission('positions.create');
    }

    public function update(User $user, Position $position): bool
    {
        return $user->hasClubPermission('positions.update');
    }

    public function delete(User $user, Position $position): bool
    {
        return $user->hasClubPermission('positions.delete');
    }
}
