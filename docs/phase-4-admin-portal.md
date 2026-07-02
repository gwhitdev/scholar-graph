# Phase 4 — Admin portal (MVP)

> **Read [`00-conventions.md`](./00-conventions.md) first.** Strict TDD. Branch: `feature/admin-portal`.

## Goal (plain English)

An admin-only area at `/admin` that shows platform health: number of users, papers saved, top search terms,
internal vs external API usage, LLM token + cost usage, and the OpenRouter credit balance vs what's
available. Non-admins and guests must be blocked.

**Depends on:** Phase 2 (the logging tables provide the numbers).

## Questions to confirm before you start

1. **How is the first admin created?** Options: (a) a `is_admin` column + a one-off seeder/command that
   promotes a named email, or (b) manual DB flip. *Recommended: add `php artisan app:make-admin {email}`
   console command so it's repeatable.* Confirm the admin email(s) to promote.
2. **OpenRouter credit endpoint shape** — we plan to call `GET /auth/key` and read
   `data.limit`, `data.usage`, `data.limit_remaining`. If the user can confirm their OpenRouter account
   exposes this (some keys return `limit: null` for unlimited), great; otherwise we render "n/a" gracefully.
3. **Any per-user PII limits on the admin users table?** e.g. should we show emails in full to admins?
   *Recommended: yes, admins see emails.* Confirm.

## Step 1 — Admin flag, command, middleware (TDD)

Write `tests/Feature/Admin/AdminAccessTest.php`:
- `it('redirects guests from admin to login')`
- `it('forbids non-admin users from admin')` (403)
- `it('allows admin users into admin dashboard')` (200 + Inertia component `admin/dashboard`)

Implement:
- Migration `add_is_admin_to_users`: boolean `is_admin` default `false`.
- `User`: cast `is_admin` → boolean; add `public function isAdmin(): bool { return $this->is_admin; }`.
  Add `is_admin` to the `#[Fillable(...)]` list.
- `UserFactory`: add a state `admin()` → `['is_admin' => true]` for tests.
- Console command:
  ```bash
  php artisan make:command MakeAdminCommand --no-interaction
  ```
  Signature `app:make-admin {email}` → find user by email, set `is_admin = true`, save, print confirmation.
- Middleware:
  ```bash
  php artisan make:middleware EnsureUserIsAdmin --no-interaction
  ```
  `handle()` → `abort_unless($request->user()?->isAdmin(), 403);`
  Register alias in `bootstrap/app.php`:
  ```php
  $middleware->alias(['admin' => \App\Http\Middleware\EnsureUserIsAdmin::class]);
  ```

## Step 2 — Metrics service (TDD, unit)

Write `tests/Unit/Services/AdminMetricsServiceTest.php` — seed factories, assert each aggregate:
- `it('counts users')`, `it('counts saved papers')`, `it('returns top search terms')`,
  `it('sums llm tokens and cost')`, `it('breaks llm usage down by model')`,
  `it('splits api usage into internal and external')`, `it('returns per-user usage totals')`.

Create `app/Services/Admin/AdminMetricsService.php` — one method per metric, each a simple aggregate query:
```php
public function userCount(): int;
public function paperCount(): int;                 // Paper::count()
public function savedPaperCount(): int;            // DB::table('project_papers')->count()
public function topSearchTerms(int $limit = 20): Collection;   // group by query, count desc
public function apiUsageBySource(): array;         // ['internal' => n, 'external' => n]
public function llmUsageTotals(): array;           // ['prompt_tokens'=>, 'completion_tokens'=>, 'cost_usd'=>]
public function llmUsageByModel(): Collection;     // model => tokens, cost
public function perUserUsage(): Collection;        // user + summed tokens/cost from llm_calls
```

## Step 3 — OpenRouter credit balance

Add to `OpenRouterService`:
```php
/** @return array{limit: float|null, usage: float|null, remaining: float|null} */
public function getKeyUsage(): array
{
    return Cache::remember('openrouter.key_usage', now()->addMinutes(5), function () {
        try {
            $data = Http::withToken($this->apiKey)->timeout(10)
                ->get($this->baseUrl.'/auth/key')->json('data');
            return [
                'limit'     => $data['limit'] ?? null,
                'usage'     => $data['usage'] ?? null,
                'remaining' => $data['limit_remaining'] ?? null,
            ];
        } catch (\Throwable $e) {
            return ['limit' => null, 'usage' => null, 'remaining' => null];
        }
    });
}
```
Test with a faked HTTP response (`Http::fake([... '/auth/key' => Http::response(['data' => [...]])])`).

## Step 4 — Routes, controller, pages

- Create `routes/admin.php` and require it from `routes/web.php`. Group:
  ```php
  Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
      Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
      Route::get('/users', [AdminController::class, 'users'])->name('users.index');
      Route::get('/usage', [AdminController::class, 'usage'])->name('usage.index');
  });
  ```
- `AdminController` (thin) injects `AdminMetricsService` + `OpenRouterService`, returns Inertia pages with
  props. Feature test asserts the component + a prop or two.
- Pages under `resources/js/pages/admin/`:
  - `dashboard.tsx` — stat tiles: users, papers, saved papers, searches, total tokens, total cost, and a
    **credits-remaining bar** (remaining / limit). If limit is null show "Unlimited / n/a".
  - `users.tsx` — table: name, email, projects, saved papers, tokens used, cost.
  - `usage.tsx` — internal vs external API counts + LLM usage by model.
  - Follow the `dataviz` skill for any chart; stat tiles must not rely on colour alone (add labels/values).
- Add an **"Admin"** nav item in `resources/js/components/app-sidebar.tsx`, shown only when
  `auth.user.is_admin` is true. Expose `is_admin` on the shared `auth.user` prop in
  `app/Http/Middleware/HandleInertiaRequests.php`.

Run `php artisan wayfinder:generate` after adding routes.

## Step 5 — Finish

```bash
php artisan test --compact
vendor/bin/pint --dirty --format agent
```

## Done when

- [ ] `app:make-admin {email}` promotes a user; `admin` middleware blocks everyone else (403 / login redirect).
- [ ] `AdminMetricsService` unit-tested; all aggregates correct.
- [ ] Dashboard shows live users/papers/searches/tokens/cost + OpenRouter credits (or graceful n/a).
- [ ] Users and usage pages render real data.
- [ ] "Admin" nav item appears only for admins.
- [ ] Full suite green, Pint clean, Wayfinder regenerated, PR opened noting Q1–Q3 answers.
