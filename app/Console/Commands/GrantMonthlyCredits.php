<?php

namespace App\Console\Commands;

use App\Models\Plan;
use App\Models\User;
use App\Services\Billing\CreditService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:grant-monthly-credits')]
#[Description('Grant monthly credit allowance to all users based on their plan')]
class GrantMonthlyCredits extends Command
{
    public function __construct(
        protected CreditService $creditService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $freePlan = Plan::where('slug', 'free')->first();

        User::with('plan')->chunk(100, function ($users) use ($freePlan) {
            foreach ($users as $user) {
                $plan = $user->plan ?? $freePlan;

                if (! $plan) {
                    continue;
                }

                $alreadyGranted = $user->creditTransactions()
                    ->where('reason', 'monthly_grant')
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->exists();

                if ($alreadyGranted) {
                    continue;
                }

                $this->creditService->grant(
                    $user,
                    $plan->monthly_credit_allowance,
                    'monthly_grant',
                );
            }
        });

        $this->info('Monthly credits granted successfully.');

        return self::SUCCESS;
    }
}
