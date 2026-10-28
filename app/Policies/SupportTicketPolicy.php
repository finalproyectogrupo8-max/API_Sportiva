<?php

namespace App\Policies;

use App\Models\SupportTicket;
use App\Models\User;

class SupportTicketPolicy
{
    public function viewAny(User $user): bool
    {
        return (bool) $user->is_platform_admin;
    }

    public function view(User $user, SupportTicket $ticket): bool
    {
        return (bool) $user->is_platform_admin;
    }

    public function update(User $user, SupportTicket $ticket): bool
    {
        return (bool) $user->is_platform_admin;
    }
}
