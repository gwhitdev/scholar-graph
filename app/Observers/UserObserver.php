<?php

namespace App\Observers;

use App\Models\Plan;
use App\Models\User;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        $freePlan = Plan::where('slug', 'free')->first();

        if (! $freePlan) {
            return;
        }

        $user->wallet()->create([
            'balance' => $freePlan->monthly_credit_allowance,
        ]);

        $user->creditTransactions()->create([
            'delta' => $freePlan->monthly_credit_allowance,
            'reason' => 'monthly_grant',
            'balance_after' => $freePlan->monthly_credit_allowance,
        ]);
    }
}
