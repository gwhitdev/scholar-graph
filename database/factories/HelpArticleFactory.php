<?php

namespace Database\Factories;

use App\Enums\PageStatus;
use App\Models\HelpArticle;
use App\Models\HelpCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<HelpArticle>
 */
class HelpArticleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(3);

        return [
            'help_category_id' => HelpCategory::factory(),
            'slug' => Str::slug($title).'-'.fake()->unique()->randomNumber(4),
            'title' => $title,
            'content' => [
                ['type' => 'heading', 'level' => 1, 'text' => $title],
                ['type' => 'paragraph', 'text' => fake()->paragraph()],
            ],
            'status' => PageStatus::Draft,
            'sort' => fake()->numberBetween(0, 100),
        ];
    }

    /**
     * Indicate that the article is published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PageStatus::Published,
        ]);
    }
}
