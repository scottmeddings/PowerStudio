<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Download;

class DownloadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Optional: wipe existing rows first
        Download::truncate();

        // Generate 500 fake download records
        Download::factory()->count(500)->create();
    }
}
