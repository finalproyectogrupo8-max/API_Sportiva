<?php

namespace App\Policies\Club;

use App\Models\Club\InventoryMovement;
use App\Models\User;

class InventoryMovementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasClubPermission('inventory.view');
    }

    public function view(User $user, InventoryMovement $movement): bool
    {
        return $user->hasClubPermission('inventory.view');
    }

    public function create(User $user): bool
    {
        return $user->hasClubPermission('inventory.adjust');
    }
}
