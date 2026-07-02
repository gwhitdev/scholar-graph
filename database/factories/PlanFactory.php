<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'slug' => fake()->unique()->slug(),
            'name' => fake()->words(2, true).' Plan',
            'price_cents' => 0,
            'monthly_credit_allowance' => 50,
            'features' => null,
        ];
    }

    public function free(): static
    {
        return $this->state(fn (array $attributes) => [
            'slug' => 'free',
            'name' => 'Free',
            'price_cents' => 0,
            'monthly_credit_allowance' => 50,
        ]);
    }

    public function pro(): static
    {
        return $this->state(fn (array $attributes) => [
            'slug' => 'pro',
            'name' => 'Pro',
            'price_cents' => 999,
            'monthly_credit_allowance' => 500,
        ]);
    }
}
