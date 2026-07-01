<?php

namespace Database\Factories;

use App\Enums\MessageRole;
use App\Models\ChatMessage;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChatMessage>
 */
class ChatMessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'synthesis_id' => null,
            'role' => fake()->randomElement([MessageRole::User, MessageRole::Assistant]),
            'content' => fake()->paragraph(),
        ];
    }
}
