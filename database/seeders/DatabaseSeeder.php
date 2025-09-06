<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DemoDataSeeder::class,
            DownloadSeeder::class, 
            PowerTimeSeeder::class,  // include if you want it in the default run
        ]);
    }
}

