<?php

namespace Database\Factories;

use App\Models\CreditWallet;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CreditWallet>
 */
class CreditWalletFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'balance' => 50,
        ];
    }
}
