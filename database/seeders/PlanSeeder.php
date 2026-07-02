<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        Plan::updateOrCreate(
            ['slug' => 'free'],
            [
                'name' => 'Free',
                'price_cents' => 0,
                'monthly_credit_allowance' => 50,
            ],
        );

        Plan::updateOrCreate(
            ['slug' => 'pro'],
            [
                'name' => 'Pro',
                'price_cents' => 999,
                'monthly_credit_allowance' => 500,
            ],
        );
    }
}
