<?php

namespace Database\Factories;

use App\Models\Paper;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Paper>
 */
class PaperFactory extends Factory
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
            'openalex_id' => 'W'.fake()->unique()->numberBetween(1000000000, 9999999999),
            'title' => fake()->sentence(),
            'abstract' => fake()->paragraph(4),
            'year' => fake()->numberBetween(2015, 2024),
            'authors' => [fake()->name(), fake()->name()],
            'doi' => '10.'.fake()->numberBetween(1000, 9999).'/'.fake()->lexify('?????'),
            'venue' => fake()->randomElement(['Nature', 'Science', 'IEEE', 'ACM', 'Springer']),
            'pages' => fake()->numberBetween(1, 50).'-'.fake()->numberBetween(51, 100),
            'cited_by_count' => fake()->numberBetween(0, 10000),
            'referenced_works' => null,
            'added_at' => now(),
        ];
    }
}
