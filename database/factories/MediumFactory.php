<?php

namespace Database\Factories;

use App\Models\Medium;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Medium>
 */
class MediumFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $filename = fake()->uuid().'.jpg';

        return [
            'disk' => 'public',
            'path' => 'media/'.$filename,
            'filename' => $filename,
            'mime' => 'image/jpeg',
            'size' => fake()->numberBetween(10000, 5000000),
            'alt' => fake()->sentence(3),
            'uploaded_by' => User::factory(),
        ];
    }
}
