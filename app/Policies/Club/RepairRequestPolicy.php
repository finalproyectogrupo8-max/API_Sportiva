<?php

namespace App\Policies\Club;

use App\Models\Club\RepairRequest;
use App\Models\User;

class RepairRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasClubPermission('repairs.view');
    }

    public function view(User $user, RepairRequest $repairRequest): bool
    {
        return $user->hasClubPermission('repairs.view');
    }

    public function create(User $user): bool
    {
        return $user->hasClubPermission('repairs.create');
    }

    public function update(User $user, RepairRequest $repairRequest): bool
    {
        return $user->hasClubPermission('repairs.update');
    }

    public function delete(User $user, RepairRequest $repairRequest): bool
    {
        return $user->hasClubPermission('repairs.delete');
    }

    public function assign(User $user, RepairRequest $repairRequest): bool
    {
        return $user->hasClubPermission('repairs.assign');
    }

    public function resolve(User $user, RepairRequest $repairRequest): bool
    {
        return $user->hasClubPermission('repairs.update');
    }
}
