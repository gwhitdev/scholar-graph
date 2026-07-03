<?php

namespace App\Actions\Support;

use App\Enums\TicketStatus;
use App\Models\SupportTicket;
use App\Models\TicketMessage;
use App\Models\User;

class AddTicketMessageAction
{
    /**
     * Add a message to a ticket and handle reopen logic.
     */
    public function execute(SupportTicket $ticket, User $user, string $body, bool $isStaff = false): TicketMessage
    {
        $message = TicketMessage::create([
            'support_ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'body' => $body,
            'is_staff' => $isStaff,
        ]);

        // Reopen resolved tickets when a non-staff user replies
        if (! $isStaff && in_array($ticket->status, [TicketStatus::Resolved, TicketStatus::Closed], true)) {
            $ticket->update(['status' => TicketStatus::Open]);
        }

        return $message;
    }
}
