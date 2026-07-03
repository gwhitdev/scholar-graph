<?php

namespace Database\Factories;

use App\Models\NavigationItem;
use App\Models\Page;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NavigationItem>
 */
class NavigationItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'location' => fake()->randomElement(['header', 'footer']),
            'label' => fake()->word(),
            'url' => '/'.fake()->slug(1),
            'sort' => fake()->numberBetween(0, 10),
            'page_id' => null,
        ];
    }

    /**
     * Associate with a page.
     */
    public function forPage(Page $page): static
    {
        return $this->state(fn (array $attributes) => [
            'page_id' => $page->id,
            'url' => '/'.$page->slug,
        ]);
    }

    /**
     * Set the location to header.
     */
    public function header(): static
    {
        return $this->state(fn (array $attributes) => [
            'location' => 'header',
        ]);
    }

    /**
     * Set the location to footer.
     */
    public function footer(): static
    {
        return $this->state(fn (array $attributes) => [
            'location' => 'footer',
        ]);
    }
}
