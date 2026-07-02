<?php

use App\Exceptions\InsufficientCreditsException;
use App\Models\CreditTransaction;
use App\Models\User;
use App\Services\Billing\CreditService;
use Database\Seeders\PlanSeeder;

beforeEach(function () {
    $this->seed(PlanSeeder::class);
    $this->service = app(CreditService::class);
    $this->user = User::factory()->create();
});

it('returns the correct balance', function () {
    expect($this->service->balance($this->user))->toBe(50);
});

it('grants credits and records a transaction', function () {
    $this->service->grant($this->user, 25, 'license_redeem');

    expect($this->service->balance($this->user))->toBe(75);

    $transaction = CreditTransaction::where('user_id', $this->user->id)
        ->where('reason', 'license_redeem')
        ->first();

    expect($transaction->delta)->toBe(25)
        ->and($transaction->balance_after)->toBe(75);
});

it('debits credits and records a transaction', function () {
    $this->service->debit($this->user, 1, 'llm_spend');

    expect($this->service->balance($this->user))->toBe(49);

    $transaction = CreditTransaction::where('user_id', $this->user->id)
        ->where('reason', 'llm_spend')
        ->first();

    expect($transaction->delta)->toBe(-1)
        ->and($transaction->balance_after)->toBe(49);
});

it('throws when debiting more than the balance', function () {
    $this->service->debit($this->user, 100, 'llm_spend');
})->throws(InsufficientCreditsException::class);
