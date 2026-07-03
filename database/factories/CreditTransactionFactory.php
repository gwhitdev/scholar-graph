<?php

namespace Database\Factories;

use App\Models\CreditTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CreditTransaction>
 */
class CreditTransactionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'delta' => 10,
            'reason' => 'monthly_grant',
            'balance_after' => 50,
            'meta' => null,
        ];
    }
}
