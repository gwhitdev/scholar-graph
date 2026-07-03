<?php

namespace App\Policies;

use App\Models\SupportTicket;
use App\Models\User;

class SupportTicketPolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, SupportTicket $supportTicket): bool
    {
        return $user->id === $supportTicket->user_id || $user->isAdmin();
    }

    /**
     * Determine whether the user can reply to the model.
     */
    public function reply(User $user, SupportTicket $supportTicket): bool
    {
        return $user->id === $supportTicket->user_id || $user->isAdmin();
    }
}
