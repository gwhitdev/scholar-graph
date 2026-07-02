# Phase 5 — Monetisation (free tier + credits + licence keys)

> **Read [`00-conventions.md`](./00-conventions.md) first.** Strict TDD. Branch: `feature/monetisation`.

## Goal (plain English)

Give every user a **credit wallet** and a **plan**. Free users get a monthly credit allowance; running a
synthesis spends credits and is blocked at zero. Users can top up by **redeeming a licence key** (minted by
an admin). Optional later sub-phase: buy credits with Stripe.

**Depends on:** Phase 2 (usage numbers) + Phase 4 (admin area to mint keys).

## Questions to confirm before you start

1. **Credit accounting model — per-action or per-token?**
   (a) *Simple:* 1 credit per synthesis, enrichment free. (b) *Metered:* convert LLM `cost_usd`/tokens into
   credits. **Recommended: (a) per-synthesis for MVP.** Confirm — this drives Step 3.
2. **Free-tier monthly allowance** — how many credits/syntheses per month? Suggest **50**. Confirm the number.
3. **Payments now or later?** MVP = licence-key redemption only (no payment integration). Stripe checkout is
   an optional Step 6 and needs `laravel/cashier` (**⚠ NEEDS APPROVAL**). **Recommended: licence keys now,
   Stripe later.** Confirm.
4. **What does "pro" unlock?** More monthly credits? Unlimited? A feature flag? Suggest: pro = larger monthly
   allowance (e.g. 1000). Confirm the plan shape.

## Step 1 — Data model (TDD)

Write `tests/Feature/Billing/WalletTest.php` first (cases listed in Step 2/3).

Tables/models (`make:model X -m`):
- `plans`: `slug` unique (`'free'|'pro'`), `name`, `price_cents` int default 0,
  `monthly_credit_allowance` int, `features` json nullable. Seed `free` and `pro` in a seeder.
- `credit_wallets`: `user_id` unique foreignId cascade, `balance` int default 0.
- `credit_transactions`: `user_id` foreignId, `delta` int (+/-), `reason` string
  (`'monthly_grant'|'llm_spend'|'license_redeem'|'purchase'`), `balance_after` int, `meta` json nullable, timestamps.
- `license_keys`: `code` string unique, `plan_id` foreignId nullable, `credits` int nullable,
  `redeemed_by` foreignId users nullable, `redeemed_at` timestamp nullable, `expires_at` timestamp nullable, timestamps.
- Add to `users`: `plan_id` foreignId nullable (default the free plan), `plan_expires_at` timestamp nullable.

Give each new user a wallet: either a `User::created` observer or do it in the Fortify
`CreateNewUser` action (already exists) — create a `credit_wallets` row + grant the free allowance.

## Step 2 — CreditService (TDD)

`tests/Feature/Billing/WalletTest.php`:
- `it('creates a wallet for a new user')`, `it('grants the monthly allowance')`,
- `it('debits credits and records a transaction')`, `it('throws when debiting more than the balance')`.

`app/Services/Billing/CreditService.php` (all wallet writes inside a DB transaction, always write a
`credit_transactions` row and set `balance_after`):
```php
public function balance(User $user): int;
public function grant(User $user, int $amount, string $reason, array $meta = []): void;   // +delta
public function debit(User $user, int $amount, string $reason, array $meta = []): void;    // -delta; throws InsufficientCreditsException if balance < amount
```
Create `app/Exceptions/InsufficientCreditsException.php`.

## Step 3 — Enforce on the synthesis path (TDD)

`tests/Feature/Billing/CreditEnforcementTest.php`:
- `it('debits one credit per synthesis')` (per Q1)
- `it('blocks synthesis when balance is zero on the free tier')` — POST `chat.store` → friendly flash error,
  `assertDatabaseCount('syntheses', 0)`.
- `it('records an llm_spend transaction')`.

Implement: in `ChatController::store` (or `CreateSynthesisAction`), before running the synthesis, check
`CreditService::balance($user) > 0` (or the per-action cost). If insufficient → redirect back with a flash
message "Out of credits — upgrade or redeem a key" (no 500). After a successful synthesis, `debit()` the cost.
Keep controller thin — put the check+debit in the action/service.

## Step 4 — Monthly grant command (TDD)

```bash
php artisan make:command GrantMonthlyCredits --no-interaction
```
Signature `app:grant-monthly-credits`. For each user on the free plan (and pro), grant their plan's
`monthly_credit_allowance` — but **idempotent per calendar month**: skip users who already have a
`credit_transactions` row with `reason='monthly_grant'` this month. Schedule it monthly in
`routes/console.php`. Test the command directly: `it('grants each user their monthly allowance once')`,
`it('does not double-grant in the same month')`.

## Step 5 — Licence keys (TDD)

Admin minting (extends Phase 4 admin area):
- `Actions/Billing/GenerateLicenseKeysAction::handle(int $count, ?int $planId, ?int $credits, ?Carbon $expiresAt): Collection`
  — creates N `license_keys` with random codes formatted `XXXX-XXXX-XXXX-XXXX`.
- Admin route `admin.licenses.index` + `admin.licenses.store`; page `admin/licenses.tsx` (mint form + list).

User redemption:
- Route `POST /settings/billing/redeem` → `RedeemLicenseKeyRequest` (`code` required) →
  `Actions/Billing/RedeemLicenseKeyAction`: find unredeemed, unexpired key → within a transaction: set
  `redeemed_by`/`redeemed_at`; if it has a `plan_id` set the user's plan (+`plan_expires_at`); if it has
  `credits` call `CreditService::grant(..., 'license_redeem')`.
- Page `resources/js/pages/settings/billing.tsx`: current plan, balance, redeem form, transaction history.

Tests `tests/Feature/Billing/LicenseRedemptionTest.php`:
`it('redeems a valid key and grants credits')`, `it('rejects an already-redeemed key')`,
`it('rejects an expired key')`, `it('only an admin can mint keys')`.

## Step 6 — (OPTIONAL, ⚠ NEEDS APPROVAL) Stripe checkout

Only if the user approves in Q3. Requires `laravel/cashier` (**ask before installing**). Add a "Buy credits"
Checkout session and a webhook (`checkout.session.completed`) that calls `CreditService::grant(..., 'purchase')`.
Add `STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET` to `.env.example`. Keep licence-key redemption as
the default path so the app ships without payments.

## Done when

- [ ] Every user has a wallet; new users get the free allowance.
- [ ] Synthesis debits credits and is blocked (friendly message) at zero.
- [ ] Monthly grant command works and is idempotent per month.
- [ ] Admins mint licence keys; users redeem them for plan/credits; invalid keys rejected.
- [ ] Billing settings page shows plan, balance, redeem form, history.
- [ ] Full suite green, Pint clean, Wayfinder regenerated, PR opened noting Q1–Q4 answers.
