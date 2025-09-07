<?php

namespace Database\Factories;

use App\Models\Episode;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class EpisodeFactory extends Factory
{
    protected $model = Episode::class;

    public function definition(): array
    {
        $title       = $this->faker->unique()->sentence(6);
        $status      = $this->faker->randomElement(['draft','published']);
        $publishedAt = $status === 'published'
            ? $this->faker->dateTimeBetween('-60 days', 'now')
            : null;

        return [
            'user_id'          => User::query()->inRandomOrder()->value('id') ?? User::factory(),
            'title'            => $title,
            'slug'             => Str::slug($title) . '-' . Str::lower(Str::random(6)),
            'description'      => $this->faker->paragraphs(2, true),
            'audio_url'        => null,
            'duration_seconds' => $this->faker->numberBetween(120, 5400),
            'status'           => $status,
            'published_at'     => $publishedAt,
            'downloads_count'  => 0,
            'comments_count'   => 0,
        ];
    }
  

    public function published(): self
    {
        return $this->state(function () {
            return [
                'status'       => 'published',
                'published_at' => now()->subDays(rand(1, 45)),
            ];
        });
    }

    public function draft(): self
    {
        return $this->state(fn () => [
            'status'       => 'draft',
            'published_at' => null,
        ]);
    }

    /**
     * Force the owner.
     */
    public function forUser(User $user): self
    {
        return $this->state(fn () => ['user_id' => $user->id]);
    }
}

