<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Episode;
use App\Models\Download;
use Database\Factories\DownloadFactory;

class DownloadSeeder extends Seeder
{
    public function run(): void
    {
        if (Episode::count() === 0) {
            Episode::factory()->count(10)->create();
        }

        Download::query()->delete();
        DownloadFactory::new()->count(500)->create();
    }
}
