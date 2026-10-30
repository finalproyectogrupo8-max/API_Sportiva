<?php

namespace App\Policies\Club;

use App\Models\Club\Sport;
use App\Models\User;

class SportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasClubPermission('sports.view');
    }

    public function view(User $user, Sport $sport): bool
    {
        return $user->hasClubPermission('sports.view');
    }

    public function create(User $user): bool
    {
        return $user->hasClubPermission('sports.create');
    }

    public function update(User $user, Sport $sport): bool
    {
        return $user->hasClubPermission('sports.update');
    }

    public function delete(User $user, Sport $sport): bool
    {
        return $user->hasClubPermission('sports.delete');
    }
}
