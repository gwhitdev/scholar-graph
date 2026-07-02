<?php

use App\Models\Plan;
use App\Models\User;
use Database\Seeders\PlanSeeder;

beforeEach(function () {
    $this->seed(PlanSeeder::class);
});

it('grants each user their monthly allowance once', function () {
    $freePlan = Plan::where('slug', 'free')->first();
    $proPlan = Plan::where('slug', 'pro')->first();

    $freeUser = User::factory()->create(['plan_id' => $freePlan->id]);
    $proUser = User::factory()->create(['plan_id' => $proPlan->id]);

    // Simulate users from last month: backdate their initial grant
    $freeUser->creditTransactions()->update(['created_at' => now()->subMonth()]);
    $proUser->creditTransactions()->update(['created_at' => now()->subMonth()]);

    // Reset balances to 0 so we can verify the grant
    $freeUser->wallet->update(['balance' => 0]);
    $proUser->wallet->update(['balance' => 0]);

    $this->artisan('app:grant-monthly-credits')
        ->assertSuccessful();

    expect($freeUser->wallet->fresh()->balance)->toBe(50)
        ->and($proUser->wallet->fresh()->balance)->toBe(500);
});

it('does not double-grant in the same month', function () {
    $user = User::factory()->create();

    // The observer already granted this month, so running the command again should not double-grant.
    $balanceBefore = $user->wallet->fresh()->balance;

    $this->artisan('app:grant-monthly-credits')->assertSuccessful();

    expect($user->wallet->fresh()->balance)->toBe($balanceBefore);
});
