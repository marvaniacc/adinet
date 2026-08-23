<?php

namespace App\Policies;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    /** Owner sees own tickets; admins see all. */
    public function view(User $user, Ticket $ticket): bool
    {
        return $user->id === $ticket->user_id || $user->isAdmin();
    }

    /**
     * Replies: the owner while the ticket isn't closed, and admins anytime.
     * An owner reply re-opens an answered ticket (handled in component).
     */
    public function reply(User $user, Ticket $ticket): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->id === $ticket->user_id && $ticket->status !== TicketStatus::Closed;
    }

    /** Either party may close; only admin may reopen. */
    public function close(User $user, Ticket $ticket): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->id === $ticket->user_id;
    }
}
