<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Download>
 */
class DownloadFactory extends Factory
{
    public function definition(): array
    {
        return [
            // Distribute across the last 90 days
            'created_at' => fake()->dateTimeBetween('-90 days', 'now'),
            'updated_at' => now(),

            // add your model’s extra columns here if you have them
            // e.g. 'episode_id' => fake()->numberBetween(1, 10),
            //      'user_id'    => fake()->numberBetween(1, 50),
        ];
    }
}
