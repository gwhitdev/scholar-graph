<?php

namespace App\Services\Billing;

use App\Exceptions\InsufficientCreditsException;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreditService
{
    /**
     * Get the current credit balance for a user.
     */
    public function balance(User $user): int
    {
        $wallet = $user->wallet;

        if (! $wallet) {
            return 0;
        }

        return $wallet->balance;
    }

    /**
     * Grant credits to a user's wallet.
     *
     * @param  array<string, mixed>  $meta
     */
    public function grant(User $user, int $amount, string $reason, array $meta = []): void
    {
        DB::transaction(function () use ($user, $amount, $reason, $meta) {
            $wallet = $user->wallet;

            $wallet->increment('balance', $amount);

            $user->creditTransactions()->create([
                'delta' => $amount,
                'reason' => $reason,
                'balance_after' => $wallet->fresh()->balance,
                'meta' => $meta ?: null,
            ]);
        });
    }

    /**
     * Debit credits from a user's wallet.
     *
     * @param  array<string, mixed>  $meta
     *
     * @throws InsufficientCreditsException
     */
    public function debit(User $user, int $amount, string $reason, array $meta = []): void
    {
        DB::transaction(function () use ($user, $amount, $reason, $meta) {
            $wallet = $user->wallet;

            if ($wallet->balance < $amount) {
                throw new InsufficientCreditsException;
            }

            $wallet->decrement('balance', $amount);

            $user->creditTransactions()->create([
                'delta' => -$amount,
                'reason' => $reason,
                'balance_after' => $wallet->fresh()->balance,
                'meta' => $meta ?: null,
            ]);
        });
    }
}
