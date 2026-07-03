<?php

namespace App\Http\Controllers\Support;

use App\Actions\Support\AddTicketMessageAction;
use App\Actions\Support\CreateTicketAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTicketMessageRequest;
use App\Http\Requests\StoreTicketRequest;
use App\Models\SupportTicket;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SupportTicketController extends Controller
{
    public function __construct(
        private CreateTicketAction $createTicketAction,
        private AddTicketMessageAction $addTicketMessageAction,
    ) {}

    /**
     * Display the user's tickets.
     */
    public function index(): Response
    {
        $tickets = auth()->user()->tickets()->withCount('messages')->latest()->get();

        return Inertia::render('support/index', [
            'tickets' => $tickets,
        ]);
    }

    /**
     * Show the form to create a ticket.
     */
    public function create(): Response
    {
        return Inertia::render('support/create');
    }

    /**
     * Store a newly created ticket.
     */
    public function store(StoreTicketRequest $request): RedirectResponse
    {
        $this->createTicketAction->execute(auth()->user(), $request->validated());

        return redirect()->route('support.tickets.index')
            ->with('success', 'Ticket created successfully.');
    }

    /**
     * Display the specified ticket.
     */
    public function show(SupportTicket $ticket): Response
    {
        $this->authorize('view', $ticket);

        $ticket->load(['messages.user', 'user']);

        return Inertia::render('support/show', [
            'ticket' => $ticket,
        ]);
    }

    /**
     * Reply to a ticket.
     */
    public function reply(StoreTicketMessageRequest $request, SupportTicket $ticket): RedirectResponse
    {
        $this->authorize('reply', $ticket);

        $this->addTicketMessageAction->execute(
            $ticket,
            auth()->user(),
            $request->validated('body'),
            auth()->user()->isAdmin(),
        );

        return redirect()->route('support.tickets.show', $ticket)
            ->with('success', 'Reply sent.');
    }
}
