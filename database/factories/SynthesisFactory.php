<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Synthesis;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Synthesis>
 */
class SynthesisFactory extends Factory
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
            'paper_ids' => [],
            'question' => fake()->paragraph(),
            'answer' => fake()->paragraph(3),
            'model_used' => null,
        ];
    }
}
