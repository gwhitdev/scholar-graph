# Phase 6 — Support ticket portal

> **Read [`00-conventions.md`](./00-conventions.md) first.** Strict TDD. Branch: `feature/support-tickets`.

## Goal (plain English)

Logged-in users can raise a **bug / feature request / general support** ticket and have a threaded
conversation with staff. Admins triage all tickets, reply, and change status. Internal only — no external
email is required for the MVP (email/push can be added later, see Phase 9).

**Depends on:** Phase 1 (ownership) + Phase 4 (admin area for the staff side).

## Questions to confirm before you start

1. **Ticket types** — is `bug | feature | support` the right set, or do they want more (e.g. `billing`)?
   *Recommended: bug / feature / support.* Confirm.
2. **Statuses** — `open | in_progress | resolved | closed` OK? *Recommended: yes.* Confirm.
3. **Should users be able to reopen a resolved/closed ticket by replying?** *Recommended: replying to a
   resolved ticket sets it back to `open`.* Confirm the desired behaviour.
4. **Notify on reply?** MVP can be in-app only. Email/push notification of staff replies is optional and, for
   push, depends on Phase 9. *Recommended: in-app only for now.* Confirm.

## Step 1 — Data model (TDD)

Write `tests/Feature/Support/SupportTicketTest.php` first (cases in Step 3).

Enums in `app/Enums/`:
- `TicketType`: `Bug='bug'`, `Feature='feature'`, `Support='support'`.
- `TicketStatus`: `Open='open'`, `InProgress='in_progress'`, `Resolved='resolved'`, `Closed='closed'`.

Tables/models (`make:model X -m`):
- `support_tickets`: `user_id` foreignId cascade, `type` string, `subject` string, `body` text,
  `status` string default `'open'`, `priority` string nullable (`low|normal|high`), timestamps.
  Casts: `type` → `TicketType`, `status` → `TicketStatus`.
- `ticket_messages`: `support_ticket_id` foreignId cascade, `user_id` foreignId, `body` text,
  `is_staff` boolean default false, timestamps.

Relationships: `SupportTicket hasMany(TicketMessage)`, `belongsTo(User)`; `TicketMessage belongsTo(SupportTicket, User)`.

## Step 2 — Policy

```bash
php artisan make:policy SupportTicketPolicy --model=SupportTicket --no-interaction
```
- `view($user, $ticket)` → `$user->id === $ticket->user_id || $user->isAdmin()`.
- `reply` → same as view.
- Admin-only status changes are guarded by the `admin` middleware on the admin routes (below), not the policy.

## Step 3 — Tests to write (RED)

`tests/Feature/Support/SupportTicketTest.php`:
- `it('lets a user create a ticket')` — POST creates a `support_tickets` row + first `ticket_messages` row.
- `it('shows a user only their own tickets')` — index lists own, not others'.
- `it('forbids viewing another users ticket')` — 403.
- `it('lets a user reply to their own ticket')` — adds a `ticket_messages` row with `is_staff=false`.
- `it('lets an admin view and reply to any ticket')` — reply row has `is_staff=true`.
- `it('lets an admin change ticket status')`.
- (If Q3 = reopen) `it('reopens a resolved ticket when the user replies')`.

## Step 4 — Routes, controllers, actions

`routes/support.php` (require from `web.php`):
```php
Route::middleware(['auth', 'verified'])->prefix('support')->name('support.')->group(function () {
    Route::get('/tickets', [SupportTicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/create', [SupportTicketController::class, 'create'])->name('tickets.create');
    Route::post('/tickets', [SupportTicketController::class, 'store'])->name('tickets.store');
    Route::get('/tickets/{ticket}', [SupportTicketController::class, 'show'])->name('tickets.show');
    Route::post('/tickets/{ticket}/reply', [SupportTicketController::class, 'reply'])->name('tickets.reply');
});
```
Admin triage (add to `routes/admin.php` from Phase 4):
```php
Route::get('/tickets', [AdminTicketController::class, 'index'])->name('tickets.index');
Route::get('/tickets/{ticket}', [AdminTicketController::class, 'show'])->name('tickets.show');
Route::post('/tickets/{ticket}/reply', [AdminTicketController::class, 'reply'])->name('tickets.reply');
Route::patch('/tickets/{ticket}/status', [AdminTicketController::class, 'updateStatus'])->name('tickets.status');
```
Thin controllers call small actions: `CreateTicketAction`, `AddTicketMessageAction` (takes `is_staff` flag +
handles the reopen rule from Q3). Requests: `StoreTicketRequest` (`type` in enum, `subject` required max 255,
`body` required), `StoreTicketMessageRequest` (`body` required).
`SupportTicketController` methods call `$this->authorize('view', $ticket)` where a ticket is bound.

## Step 5 — Frontend

- User pages under `resources/js/pages/support/`:
  `index.tsx` (my tickets with type + status badges), `create.tsx` (type select, subject, body),
  `show.tsx` (message thread + reply box; staff messages visually distinguished and labelled for screen readers).
- Admin pages under `resources/js/pages/admin/`:
  `tickets.tsx` (all tickets, filter by status/type), `ticket-show.tsx` (thread, reply, status control).
- Add a **"Support"** item to the authenticated nav. Reuse existing UI kit (badge, card, textarea, button).

Run `php artisan wayfinder:generate` after adding routes.

## Done when

- [ ] Users create, list, view, and reply to their own tickets; cannot see others' (403).
- [ ] Admins see all tickets, reply (marked `is_staff`), and change status.
- [ ] Reopen-on-reply behaves as confirmed in Q3.
- [ ] Full suite green, Pint clean, Wayfinder regenerated, PR opened noting Q1–Q4 answers.
