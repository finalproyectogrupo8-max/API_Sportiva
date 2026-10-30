<?php

namespace App\Policies\Club;

use App\Models\Club\Coach;
use App\Models\User;

class CoachPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasClubPermission('coaches.view');
    }

    public function view(User $user, Coach $coach): bool
    {
        return $user->hasClubPermission('coaches.view');
    }

    public function create(User $user): bool
    {
        return $user->hasClubPermission('coaches.create');
    }

    public function update(User $user, Coach $coach): bool
    {
        return $user->hasClubPermission('coaches.update');
    }

    public function delete(User $user, Coach $coach): bool
    {
        return $user->hasClubPermission('coaches.delete');
    }
}
