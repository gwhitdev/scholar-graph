<?php

use App\Models\User;
use Database\Seeders\PlanSeeder;

beforeEach(function () {
    $this->seed(PlanSeeder::class);
});

it('creates a wallet for a new user', function () {
    $user = User::factory()->create();

    expect($user->wallet)->not->toBeNull()
        ->and($user->wallet->balance)->toBe(50);
});

it('records an initial monthly grant transaction for a new user', function () {
    $user = User::factory()->create();

    expect($user->creditTransactions)->toHaveCount(1)
        ->and($user->creditTransactions->first()->reason)->toBe('monthly_grant')
        ->and($user->creditTransactions->first()->delta)->toBe(50);
});
