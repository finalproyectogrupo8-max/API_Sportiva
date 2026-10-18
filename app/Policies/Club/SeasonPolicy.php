<?php

namespace App\Policies\Club;

use App\Models\Club\Season;
use App\Models\User;

class SeasonPolicy
{
    public function viewAny(User $user): bool
    {
        return method_exists($user, 'hasClubPermission') && $user->hasClubPermission('seasons.view');
    }

    public function view(User $user, Season $season): bool
    {
        return method_exists($user, 'hasClubPermission') && $user->hasClubPermission('seasons.view');
    }

    public function create(User $user): bool
    {
        return method_exists($user, 'hasClubPermission') && $user->hasClubPermission('seasons.create');
    }

    public function update(User $user, Season $season): bool
    {
        return method_exists($user, 'hasClubPermission') && $user->hasClubPermission('seasons.update');
    }

    public function delete(User $user, Season $season): bool
    {
        return method_exists($user, 'hasClubPermission') && $user->hasClubPermission('seasons.delete');
    }
}
