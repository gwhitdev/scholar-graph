<?php

namespace Database\Factories;

use App\Models\Collection;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Collection>
 */
class CollectionFactory extends Factory
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
            'user_id' => User::factory(),
            'name' => fake()->words(2, true),
            'color' => fake()->randomElement(config('collections.colors', ['sage'])),
            'position' => 0,
        ];
    }

    /**
     * Set the collection to belong to the given project and its owner.
     */
    public function forProject(Project $project): static
    {
        return $this->state([
            'project_id' => $project->id,
            'user_id' => $project->user_id,
        ]);
    }
}
