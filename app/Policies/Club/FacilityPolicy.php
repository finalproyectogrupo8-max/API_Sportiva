<?php

namespace App\Policies\Club;

use App\Models\Club\Facility;
use App\Models\User;

class FacilityPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasClubPermission('facilities.view');
    }

    public function view(User $user, Facility $facility): bool
    {
        return $user->hasClubPermission('facilities.view');
    }

    public function create(User $user): bool
    {
        return $user->hasClubPermission('facilities.create');
    }

    public function update(User $user, Facility $facility): bool
    {
        return $user->hasClubPermission('facilities.update');
    }

    public function delete(User $user, Facility $facility): bool
    {
        return $user->hasClubPermission('facilities.delete');
    }
}
