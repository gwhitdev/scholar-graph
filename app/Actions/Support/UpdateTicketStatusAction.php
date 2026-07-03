<?php

namespace App\Actions\Support;

use App\Enums\TicketStatus;
use App\Models\SupportTicket;

class UpdateTicketStatusAction
{
    /**
     * Update the status of a support ticket.
     */
    public function execute(SupportTicket $ticket, TicketStatus|string $status): void
    {
        $ticket->update(['status' => $status]);
    }
}
