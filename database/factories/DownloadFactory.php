<?php

namespace Database\Factories;

use App\Models\Download;
use App\Models\Episode;
use Illuminate\Database\Eloquent\Factories\Factory;

class DownloadFactory extends Factory
{
    protected $model = Download::class;

    public function definition(): array
    {
        $ts = $this->faker->dateTimeBetween('-90 days', 'now');

        return [
            'episode_id' => Episode::query()->inRandomOrder()->value('id') ?? Episode::factory(),
            'user_agent' => $this->faker->userAgent(),
            'ip_address' => $this->faker->ipv4(),
            'created_at' => $ts,
            'updated_at' => $ts,
        ];
    }
}
