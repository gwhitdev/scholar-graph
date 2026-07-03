<?php

namespace App\Actions\Support;

use App\Enums\TicketStatus;
use App\Enums\TicketType;
use App\Models\SupportTicket;
use App\Models\TicketMessage;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateTicketAction
{
    /**
     * Create a support ticket with its initial message.
     *
     * @param  array{type: TicketType|string, subject: string, body: string}  $data
     */
    public function execute(User $user, array $data): SupportTicket
    {
        return DB::transaction(function () use ($user, $data): SupportTicket {
            $ticket = SupportTicket::create([
                'user_id' => $user->id,
                'type' => $data['type'],
                'subject' => $data['subject'],
                'body' => $data['body'],
                'status' => TicketStatus::Open,
            ]);

            TicketMessage::create([
                'support_ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'body' => $data['body'],
                'is_staff' => false,
            ]);

            return $ticket;
        });
    }
}
