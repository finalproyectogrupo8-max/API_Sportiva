<?php

namespace App\Policies\Club;

use App\Models\Club\MaintenanceRecord;
use App\Models\User;

class MaintenanceRecordPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasClubPermission('maintenance.view');
    }

    public function view(User $user, MaintenanceRecord $record): bool
    {
        return $user->hasClubPermission('maintenance.view');
    }

    public function create(User $user): bool
    {
        return $user->hasClubPermission('maintenance.create');
    }

    public function update(User $user, MaintenanceRecord $record): bool
    {
        return $user->hasClubPermission('maintenance.update');
    }

    public function delete(User $user, MaintenanceRecord $record): bool
    {
        return $user->hasClubPermission('maintenance.delete');
    }
}
