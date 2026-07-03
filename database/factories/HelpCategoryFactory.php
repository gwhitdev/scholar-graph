<?php

namespace Database\Factories;

use App\Models\HelpCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HelpCategory>
 */
class HelpCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->words(2, true);

        return [
            'slug' => str($title)->slug()->append('-'.fake()->unique()->randomNumber(3))->toString(),
            'title' => str($title)->title()->toString(),
            'sort' => fake()->numberBetween(0, 100),
        ];
    }
}
