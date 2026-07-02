# Phase 1 — Ownership hardening (BelongsToUser on everything)

> **Read [`00-conventions.md`](./00-conventions.md) first.** Follow strict TDD. Branch: `feature/ownership-hardening`.

## Goal (plain English)

Right now a logged-in user could potentially reach another user's project/paper/chat by guessing an ID.
This phase makes that impossible and **proves it with tests**. We add Laravel Policies, scope the nested
route bindings, and call `authorize()` in every controller method that receives a `Project` or `Paper`.

**Depends on:** nothing. Do this phase first — everything else builds on it.

## Questions to confirm before you start

Ask the user, wait for answers, record them in the PR description:

1. **Response code for forbidden access — 403 or 404?**
   A `403` says "this exists but you can't have it"; a `404` hides existence entirely. Laravel policies
   return `403` by default. *Recommended: 403 (simpler, standard).* Confirm which the user wants; the test
   assertions depend on the answer.
2. **Should we add the optional `BelongsToUser` trait now**, or just policies + scoped bindings?
   *Recommended: add the trait too (small, reusable, used again in later phases).*

## What already exists (facts about the current code)

- There are **no policies** yet (`app/Policies/` does not exist).
- `routes/projects.php` groups all project/paper/chat/prompt routes under `['auth','verified']` with a
  `projects` prefix. Nested routes look like `/{project}/papers/{paper}`.
- `Project` has `user_id` and `belongsTo(User)`. `Paper`, `Synthesis`, `ChatMessage` reach the user
  **through** their `project`.
- Controllers (`ProjectController`, `PaperController`, `ChatController`, `PromptController`) are already thin.

## Step 1 — Write the isolation tests FIRST (RED)

Create `tests/Feature/Authorization/OwnershipIsolationTest.php`. Use two users, A and B, where the resource
belongs to B and the request is made by A. (Replace `403` with `404` if the user chose 404 in Q1.)

Cases to write (`it('...')`):

- `it('forbids viewing another users project')` — `actingAs($userA)->get(route('projects.show', $projectB))->assertForbidden();`
- `it('forbids deleting another users project')` — DELETE → forbidden; `assertDatabaseHas('projects', ['id' => $projectB->id])`.
- `it('forbids searching papers in another users project')` — GET `papers.search` → forbidden.
- `it('forbids adding a paper to another users project')` — POST `papers.store` → forbidden.
- `it('forbids enriching a paper in another users project')` — POST `papers.enrich` → forbidden.
- `it('forbids deleting a paper from another users project')` — DELETE `papers.destroy` → forbidden.
- `it('forbids posting chat to another users project')` — POST `chat.store` → forbidden.
- `it('forbids updating the prompt of another users project')` — PUT `projects.prompt.update` → forbidden.
- `it('returns 404 when a paper does not belong to the project in the url')` — a real paper of project X
  requested under project Y's URL → `assertNotFound()` (this proves scoped bindings work).
- `it('lets the owner perform each action')` — same calls as `$userB` succeed (200/302, not forbidden).

Run: `php artisan test --compact --filter=OwnershipIsolationTest` → everything should FAIL now.

## Step 2 — Create the policies (GREEN)

```bash
php artisan make:policy ProjectPolicy --model=Project --no-interaction
php artisan make:policy PaperPolicy --model=Paper --no-interaction
php artisan make:policy SynthesisPolicy --model=Synthesis --no-interaction
php artisan make:policy ChatMessagePolicy --model=ChatMessage --no-interaction
```

Laravel 13 auto-discovers `Model → ModelPolicy`, so no manual registration is needed.

`ProjectPolicy` — every method compares ownership:
```php
public function view(User $user, Project $project): bool   { return $user->id === $project->user_id; }
public function update(User $user, Project $project): bool  { return $user->id === $project->user_id; }
public function delete(User $user, Project $project): bool  { return $user->id === $project->user_id; }
```

`PaperPolicy`, `SynthesisPolicy`, `ChatMessagePolicy` resolve through the project:
```php
public function view(User $user, Paper $paper): bool   { return $user->id === $paper->project->user_id; }
public function update(User $user, Paper $paper): bool  { return $user->id === $paper->project->user_id; }
public function delete(User $user, Paper $paper): bool  { return $user->id === $paper->project->user_id; }
```
> After Phase 3, `Paper` is shared and has no single `project`. Phase 3 updates `PaperPolicy` to authorize
> via the **project in the route** instead of the paper. For now, project-through-paper is correct.

## Step 3 — Enforce in routes and controllers

In `routes/projects.php`, add `->scopeBindings()` to the group so `/{project}/papers/{paper}` automatically
404s when the paper is not a child of that project:
```php
Route::middleware(['auth', 'verified'])->prefix('projects')->scopeBindings()->group(function () {
    // ...unchanged route definitions...
});
```

In each controller method that receives a bound model, add ONE authorize line at the top. Examples:
```php
// ProjectController
public function show(Request $request, Project $project): Response { $this->authorize('view', $project); /* ... */ }
public function destroy(Request $request, Project $project): RedirectResponse { $this->authorize('delete', $project); /* ... */ }

// PaperController — the project comes from the URL; authorize against it
public function search(Request $request, Project $project): JsonResponse { $this->authorize('view', $project); /* ... */ }
public function store(StorePaperRequest $request, Project $project): RedirectResponse { $this->authorize('update', $project); /* ... */ }
public function enrich(Request $request, Project $project, Paper $paper): RedirectResponse { $this->authorize('update', $project); /* ... */ }
public function destroy(Request $request, Project $project, Paper $paper): RedirectResponse { $this->authorize('update', $project); /* ... */ }

// ChatController
public function store(StoreChatMessageRequest $request, Project $project): RedirectResponse { $this->authorize('update', $project); /* ... */ }

// PromptController
public function update(Request $request, Project $project): RedirectResponse { $this->authorize('update', $project); /* ... */ }
```
> If a controller doesn't already `use AuthorizesRequests`, add the trait
> (`use Illuminate\Foundation\Auth\Access\AuthorizesRequests;`) to the base `App\Http\Controllers\Controller`.

Re-run the test until GREEN: `php artisan test --compact --filter=OwnershipIsolationTest`.

## Step 4 — Optional reusable `BelongsToUser` trait (if approved in Q2)

Create `app/Models/Concerns/BelongsToUser.php`:
```php
<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToUser
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @param  Builder<static>  $query */
    public function scopeOwnedBy(Builder $query, User $user): Builder
    {
        return $query->where($this->qualifyColumn('user_id'), $user->id);
    }
}
```
Apply `use BelongsToUser;` to `Project` (it already has `user_id`). Add a small unit test
`tests/Unit/Models/BelongsToUserTest.php` → `it('scopes projects to the owner')`.

> Do **not** add an automatic global scope keyed on `Auth::id()`. It breaks the `EnrichPaperJob` queue job
> (no auth context) and the admin portal (needs to see all users). Explicit policies are the mechanism.

## Step 5 — Finish

```bash
php artisan test --compact                 # whole suite: no regressions
vendor/bin/pint --dirty --format agent
```

## Done when

- [ ] All `OwnershipIsolationTest` cases pass with the agreed code (403 or 404).
- [ ] Scoped bindings make mismatched project/paper URLs 404.
- [ ] Every project/paper/chat/prompt controller method calls `authorize()`.
- [ ] `BelongsToUser` trait added + tested (if approved).
- [ ] Full suite green, Pint clean.
- [ ] Branch pushed, PR opened describing the Q1/Q2 answers.
