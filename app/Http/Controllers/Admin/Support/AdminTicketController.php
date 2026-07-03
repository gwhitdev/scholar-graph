<?php

namespace App\Http\Controllers\Admin\Support;

use App\Actions\Support\AddTicketMessageAction;
use App\Actions\Support\UpdateTicketStatusAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTicketMessageRequest;
use App\Http\Requests\UpdateTicketStatusRequest;
use App\Mail\StaffTicketReplyMail;
use App\Models\SupportTicket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class AdminTicketController extends Controller
{
    public function __construct(
        private AddTicketMessageAction $addTicketMessageAction,
        private UpdateTicketStatusAction $updateTicketStatusAction,
    ) {}

    /**
     * Display all tickets.
     */
    public function index(): Response
    {
        $tickets = SupportTicket::with(['user', 'messages'])->latest()->get();

        return Inertia::render('admin/tickets/index', [
            'tickets' => $tickets,
        ]);
    }

    /**
     * Display the specified ticket.
     */
    public function show(SupportTicket $ticket): Response
    {
        $ticket->load(['messages.user', 'user']);

        return Inertia::render('admin/tickets/show', [
            'ticket' => $ticket,
        ]);
    }

    /**
     * Reply to a ticket as staff.
     */
    public function reply(StoreTicketMessageRequest $request, SupportTicket $ticket): RedirectResponse
    {
        $admin = auth()->user();

        $this->addTicketMessageAction->execute(
            $ticket,
            $admin,
            $request->validated('body'),
            true,
        );

        // Send email notification to ticket owner
        Mail::to($ticket->user)->send(new StaffTicketReplyMail($ticket, $admin));

        return redirect()->route('admin.tickets.show', $ticket)
            ->with('success', 'Reply sent.');
    }

    /**
     * Update ticket status.
     */
    public function updateStatus(UpdateTicketStatusRequest $request, SupportTicket $ticket): RedirectResponse
    {
        $this->updateTicketStatusAction->execute($ticket, $request->validated('status'));

        return redirect()->route('admin.tickets.show', $ticket)
            ->with('success', 'Status updated.');
    }
}
