<?php

namespace Database\Factories;

use App\Enums\PageStatus;
use App\Models\Page;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Page>
 */
class PageFactory extends Factory
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
            'slug' => Str::slug($title).'-'.fake()->unique()->randomNumber(4),
            'title' => $title,
            'content' => [
                ['type' => 'heading', 'level' => 1, 'text' => $title],
                ['type' => 'paragraph', 'text' => fake()->paragraph()],
            ],
            'status' => PageStatus::Draft,
            'seo_title' => fake()->sentence(4),
            'seo_description' => fake()->sentence(10),
            'og_image' => null,
            'published_at' => null,
            'author_id' => User::factory(),
        ];
    }

    /**
     * Indicate that the page is published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PageStatus::Published,
            'published_at' => now(),
        ]);
    }

    /**
     * Set a specific slug.
     */
    public function slug(string $slug): static
    {
        return $this->state(fn (array $attributes) => [
            'slug' => $slug,
        ]);
    }
}
