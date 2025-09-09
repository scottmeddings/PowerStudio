<?php

namespace Database\Seeders;        // ← IMPORTANT

use Illuminate\Database\Seeder;
use App\Models\Episode;

class EpisodeWithTranscriptSeeder extends Seeder
{
    public function run(): void
    {
        $e = Episode::create([
            'user_id'     => 1,                 // adjust
            'title'       => 'Seeded Episode',
            'slug'        => 'seeded-episode',
            'description' => 'Demo seeded episode.',
            'audio_url'   => '/storage/audio/demo.mp3',
            'status'      => 'draft',
        ]);

        $e->transcript()->create([
            'format'      => 'txt',
            'duration_ms' => 120000,
            'body'        => "Intro\nWe talk about seeding data.\nOutro",
        ]);
    }
}
