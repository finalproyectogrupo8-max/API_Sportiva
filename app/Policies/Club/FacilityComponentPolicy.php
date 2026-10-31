<?php

namespace App\Policies\Club;

use App\Models\Club\FacilityComponent;
use App\Models\User;

class FacilityComponentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasClubPermission('facilities.view');
    }

    public function view(User $user, FacilityComponent $component): bool
    {
        return $user->hasClubPermission('facilities.view');
    }

    public function create(User $user): bool
    {
        return $user->hasClubPermission('facilities.update') || $user->hasClubPermission('facilities.create');
    }

    public function update(User $user, FacilityComponent $component): bool
    {
        return $user->hasClubPermission('facilities.update');
    }

    public function delete(User $user, FacilityComponent $component): bool
    {
        return $user->hasClubPermission('facilities.update') || $user->hasClubPermission('facilities.delete');
    }
}
