# 00 — Conventions (read this before every phase)

You are implementing one phase of the ScholarGraph roadmap. This file is the rulebook. Obey it exactly.

## The stack (do not change versions or add packages without approval)

- PHP 8.5, Laravel 13
- Inertia v3 + React 19 (pages in `resources/js/pages`, components in `resources/js/components`)
- Pest 4 for tests (Feature tests in `tests/Feature`, Unit in `tests/Unit`, Browser in `tests/Browser`)
- PostgreSQL
- Wayfinder generates typed TS route helpers (imported from `@/actions/...` and `@/routes/...`)
- Fortify for auth (already complete — never touch auth)
- shadcn-style UI kit already exists in `resources/js/components/ui` (button, card, input, dialog, badge,
  skeleton, spinner, textarea, checkbox, label, etc.) — **reuse these, do not hand-roll.**
- `react-markdown` is already installed.

## The 10 ground rules (from CLAUDE.md — non-negotiable)

1. **Strict TDD.** Write a failing test FIRST. Run it. See red. Then write the minimum code to go green.
   Then refactor. Never write implementation before its test.
2. **One branch per phase:** `git checkout -b feature/<phase-slug>`. When green, commit and
   `gh pr create`. Never merge without review.
3. **Thin controllers.** Controllers only: validate input, call a Service/Action, return a response.
   All business logic goes in `app/Services/` (broad operations) or `app/Actions/` (single-purpose).
4. **Reusable components.** Extract shared traits/services (backend) and shared React components/hooks
   (frontend). Check for an existing one before creating a new one.
5. **Typed PHP.** Every method has parameter types and a return type. Use PHPDoc array shapes for arrays.
   Use PHP 8 constructor property promotion: `public function __construct(private Foo $foo) {}`.
6. **Accessibility (WCAG AA).** `<label>` on every input; `aria-label` on icon-only buttons; focus trap +
   Esc-to-close on dialogs; respect `prefers-reduced-motion`; never rely on colour alone.
7. **Format PHP** after changes: `vendor/bin/pint --dirty --format agent`.
8. **Regenerate routes** after adding/editing any route: `php artisan wayfinder:generate`.
9. **Run tests** narrowly while iterating, then the whole phase folder before committing.
10. **No new dependency** (composer/npm) without explicit user approval. Steps needing one are marked
    **⚠ NEEDS APPROVAL** — stop and ask first.

## The exact TDD loop (repeat for every unit of work)

```bash
# 1. Create the test file
php artisan make:test --pest Feature/SomethingTest      # feature test
php artisan make:test --pest --unit SomethingTest       # unit test

# 2. Write the test cases in that file (they must fail for the right reason).

# 3. RED — run it and confirm failure
php artisan test --compact --filter=SomethingTest

# 4. Create the code (migration/model/service/etc.) with make: commands
php artisan make:migration create_widgets_table --no-interaction
php artisan make:model Widget --no-interaction
# ...etc

# 5. GREEN — run again until it passes
php artisan test --compact --filter=SomethingTest

# 6. Refactor if needed, keep tests green.

# 7. Format + regenerate routes
vendor/bin/pint --dirty --format agent
php artisan wayfinder:generate        # only if routes changed
```

## Useful commands

```bash
php artisan migrate                 # after each new migration
php artisan make:model X -mf        # model + migration + factory
php artisan make:policy XPolicy --model=X --no-interaction
php artisan make:middleware X --no-interaction
php artisan make:request StoreXRequest --no-interaction
php artisan make:class Services/X --no-interaction
php artisan make:class Actions/Domain/DoThingAction --no-interaction
php artisan make:enum XStatus --no-interaction     # or create in app/Enums manually
php artisan route:list --except-vendor             # inspect routes
```

## Test conventions (Pest 4)

- Feature tests use `RefreshDatabase` (already applied globally in `tests/Pest.php`).
- Use factories, not manual model creation: `User::factory()->create()`, `Project::factory()->for($user)->create()`.
- Authenticate with `$this->actingAs($user)`.
- Inertia assertions:
  ```php
  $this->actingAs($user)->get(route('projects.index'))
      ->assertInertia(fn (Assert $page) => $page->component('projects/index')->has('projects', 1));
  ```
- Fake external HTTP so no live calls happen in CI:
  ```php
  Http::fake(['openrouter.ai/*' => Http::response(['choices' => [['message' => ['content' => 'hi']]]])]);
  ```

## Ownership model (true after Phase 1)

Every user-owned record reaches `User` through `Project`:
`User → Project → (Paper via pivot after Phase 3 / Synthesis / ChatMessage)`.
Ownership is enforced by **Policies + scoped route bindings + `authorize()` calls**, proven by tests.
Do **not** add an `Auth::id()` global scope — it breaks queue jobs and the admin portal.

## Decision log

Some phases require a product decision from the user before coding. Each phase file lists its own
"Questions to confirm before you start". Ask them, record the answers at the top of your PR description,
and only then begin. If the user already answered a question in chat, restate their answer back to confirm.

## What "Done" means

Each phase ends with a "Done when" checklist. All items must be true:
tests green (phase folder + no regressions elsewhere), Pint clean, Wayfinder regenerated if routes changed,
and a PR opened. Then stop.
