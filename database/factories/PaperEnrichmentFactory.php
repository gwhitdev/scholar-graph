<?php

namespace Database\Factories;

use App\Models\Paper;
use App\Models\PaperEnrichment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaperEnrichment>
 */
class PaperEnrichmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'paper_id' => Paper::factory(),
            'semantic_scholar_id' => fake()->regexify('[a-z0-9]{20}'),
            'tldr' => fake()->paragraph(),
            'tldr_source' => 'semantic_scholar',
            'influential_citation_count' => fake()->numberBetween(0, 500),
            'related_paper_ids' => null,
            'enriched_at' => now(),
        ];
    }
}
