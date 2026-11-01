<?php

namespace App\Policies\Club;

use App\Models\Club\ApparelItem;
use App\Models\User;

class ApparelItemPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasClubPermission('apparel_items.view');
    }

    public function view(User $user, ApparelItem $apparelItem): bool
    {
        return $user->hasClubPermission('apparel_items.view');
    }

    public function create(User $user): bool
    {
        return $user->hasClubPermission('apparel_items.create');
    }

    public function update(User $user, ApparelItem $apparelItem): bool
    {
        return $user->hasClubPermission('apparel_items.update');
    }

    public function delete(User $user, ApparelItem $apparelItem): bool
    {
        return $user->hasClubPermission('apparel_items.delete');
    }
}
