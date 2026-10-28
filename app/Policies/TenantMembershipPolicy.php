<?php

namespace App\Policies;

use App\Models\TenantMembership;
use App\Models\User;

class TenantMembershipPolicy
{
    public function viewAny(User $user): bool
    {
        return (bool) $user->is_platform_admin;
    }

    public function create(User $user): bool
    {
        return (bool) $user->is_platform_admin;
    }

    public function update(User $user, TenantMembership $membership): bool
    {
        return (bool) $user->is_platform_admin;
    }
}
