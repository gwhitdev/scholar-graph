<?php

use App\Enums\TicketStatus;
use App\Mail\StaffTicketReplyMail;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
});

test('lets a user create a ticket', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('support.tickets.store'), [
            'type' => 'bug',
            'subject' => 'Paper search broken',
            'body' => 'The search returns no results for valid queries.',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('support_tickets', [
        'user_id' => $user->id,
        'type' => 'bug',
        'subject' => 'Paper search broken',
        'status' => 'open',
    ]);

    $ticket = SupportTicket::where('subject', 'Paper search broken')->first();
    $this->assertDatabaseHas('ticket_messages', [
        'support_ticket_id' => $ticket->id,
        'user_id' => $user->id,
        'body' => 'The search returns no results for valid queries.',
        'is_staff' => false,
    ]);
});

test('shows a user only their own tickets', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    SupportTicket::factory()->for($user)->count(2)->create();
    SupportTicket::factory()->for($otherUser)->count(3)->create();

    $this->actingAs($user)
        ->get(route('support.tickets.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('support/index')
            ->has('tickets', 2)
        );
});

test('forbids viewing another users ticket', function () {
    $user = User::factory()->create();
    $otherTicket = SupportTicket::factory()->create();

    $this->actingAs($user)
        ->get(route('support.tickets.show', $otherTicket))
        ->assertForbidden();
});

test('lets a user reply to their own ticket', function () {
    $user = User::factory()->create();
    $ticket = SupportTicket::factory()->for($user)->create();

    $this->actingAs($user)
        ->post(route('support.tickets.reply', $ticket), [
            'body' => 'Here is more info about the issue.',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('ticket_messages', [
        'support_ticket_id' => $ticket->id,
        'user_id' => $user->id,
        'body' => 'Here is more info about the issue.',
        'is_staff' => false,
    ]);
});

test('lets an admin view and reply to any ticket', function () {
    $user = User::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $ticket = SupportTicket::factory()->for($user)->create();

    $this->actingAs($admin)
        ->get(route('admin.tickets.show', $ticket))
        ->assertOk();

    $this->actingAs($admin)
        ->post(route('admin.tickets.reply', $ticket), [
            'body' => 'We are looking into this.',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('ticket_messages', [
        'support_ticket_id' => $ticket->id,
        'user_id' => $admin->id,
        'body' => 'We are looking into this.',
        'is_staff' => true,
    ]);
});

test('lets an admin change ticket status', function () {
    $user = User::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $ticket = SupportTicket::factory()->for($user)->create();

    $this->actingAs($admin)
        ->patch(route('admin.tickets.status', $ticket), [
            'status' => 'in_progress',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('support_tickets', [
        'id' => $ticket->id,
        'status' => 'in_progress',
    ]);
});

test('reopens a resolved ticket when the user replies', function () {
    $user = User::factory()->create();
    $ticket = SupportTicket::factory()->for($user)->create(['status' => TicketStatus::Resolved]);

    $this->actingAs($user)
        ->post(route('support.tickets.reply', $ticket), [
            'body' => 'I still have this issue.',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('support_tickets', [
        'id' => $ticket->id,
        'status' => 'open',
    ]);
});

test('sends email notification when staff replies', function () {
    $user = User::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $ticket = SupportTicket::factory()->for($user)->create();

    $this->actingAs($admin)
        ->post(route('admin.tickets.reply', $ticket), [
            'body' => 'We have fixed the issue.',
        ]);

    Mail::assertSent(StaffTicketReplyMail::class);
});

test('validates ticket creation input', function (array $data, string $errorKey) {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('support.tickets.store'), $data)
        ->assertSessionHasErrors($errorKey);
})->with([
    'missing subject' => [['type' => 'bug', 'subject' => '', 'body' => 'Some body'], 'subject'],
    'missing body' => [['type' => 'bug', 'subject' => 'A subject', 'body' => ''], 'body'],
    'invalid type' => [['type' => 'invalid', 'subject' => 'A subject', 'body' => 'Some body'], 'type'],
]);
