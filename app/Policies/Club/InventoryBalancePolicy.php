<?php

namespace App\Policies\Club;

use App\Models\Club\InventoryBalance;
use App\Models\User;

class InventoryBalancePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasClubPermission('inventory.view');
    }

    public function view(User $user, InventoryBalance $balance): bool
    {
        return $user->hasClubPermission('inventory.view');
    }
}
