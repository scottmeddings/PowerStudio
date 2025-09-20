<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // DemoDataSeeder::class,
            // DownloadSeeder::class,
            // DistributionDemoSeeder::class,
            // PowerTimeSeeder::class,
            // EpisodeWithTranscriptSeeder::class,
            AdminUserSeeder::class,   // ← just the class name
        ]);
    }
}
