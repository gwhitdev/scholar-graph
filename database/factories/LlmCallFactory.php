<?php

namespace Database\Factories;

use App\Models\LlmCall;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LlmCall>
 */
class LlmCallFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'model' => 'qwen-plus',
            'context_type' => 'synthesis',
            'context_id' => fake()->randomNumber(),
            'prompt' => fake()->sentence(),
            'prompt_tokens' => fake()->numberBetween(1, 1000),
            'completion_tokens' => fake()->numberBetween(1, 1000),
            'cost_usd' => fake()->randomFloat(6, 0, 1),
            'duration_ms' => fake()->numberBetween(100, 5000),
            'status' => 'success',
        ];
    }
}
