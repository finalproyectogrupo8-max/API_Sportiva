<?php

namespace App\Policies\Club;

use App\Models\Club\StorageLocation;
use App\Models\User;

class StorageLocationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasClubPermission('storage_locations.view');
    }

    public function view(User $user, StorageLocation $storageLocation): bool
    {
        return $user->hasClubPermission('storage_locations.view');
    }

    public function create(User $user): bool
    {
        return $user->hasClubPermission('storage_locations.create');
    }

    public function update(User $user, StorageLocation $storageLocation): bool
    {
        return $user->hasClubPermission('storage_locations.update');
    }

    public function delete(User $user, StorageLocation $storageLocation): bool
    {
        return $user->hasClubPermission('storage_locations.delete');
    }
}
