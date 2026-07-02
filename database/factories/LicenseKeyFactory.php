<?php

namespace Database\Factories;

use App\Models\LicenseKey;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<LicenseKey>
 */
class LicenseKeyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => strtoupper(Str::random(4).'-'.Str::random(4).'-'.Str::random(4).'-'.Str::random(4)),
            'plan_id' => null,
            'credits' => 100,
            'redeemed_by' => null,
            'redeemed_at' => null,
            'expires_at' => now()->addYear(),
        ];
    }

    public function redeemed(): static
    {
        return $this->state(fn (array $attributes) => [
            'redeemed_by' => User::factory(),
            'redeemed_at' => now(),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subDay(),
        ]);
    }

    public function forPlan(Plan $plan): static
    {
        return $this->state(fn (array $attributes) => [
            'plan_id' => $plan->id,
        ]);
    }
}
