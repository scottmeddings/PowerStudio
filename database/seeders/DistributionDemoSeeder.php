<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Episode;
use App\Models\Download;
use App\Models\PodcastDirectory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DistributionDemoSeeder extends Seeder
{
    public function run(): void
    {
        // 1) Pick (or create) a demo user
        $user = User::first() ?? User::factory()->create([
            'name' => 'PowerTime Demo',
            'email' => 'demo@powertime.au',
            'password' => bcrypt('password'),
        ]);

        // 2) Ensure they have ~12 published episodes over the last 3 months
        $have = Episode::where('user_id', $user->id)->count();
        if ($have < 10) {
            $count = 12 - $have;
            for ($i = 0; $i < $count; $i++) {
                $title = 'E' . str_pad((string) ($have + $i + 1), 2, '0', STR_PAD_LEFT) . ': ' .
                    fake()->catchPhrase();

                Episode::create([
                    'user_id'          => $user->id,
                    'title'            => $title,
                    'slug'             => Str::slug($title) . '-' . Str::lower(Str::random(6)),
                    'description'      => fake()->paragraphs(2, true),
                    'audio_url'        => null,
                    'duration_seconds' => fake()->numberBetween(900, 3600),
                    'status'           => 'published',
                    'published_at'     => now()->subDays(fake()->numberBetween(5, 70)),
                ]);
            }
        }

        $episodes = Episode::where('user_id', $user->id)->pluck('id')->all();
        if (empty($episodes)) {
            $this->command?->warn('No episodes found to seed downloads for.');
            return;
        }

        // 3) Generate realistic downloads for the last 60 days
        //    We’ll give each episode its own baseline & some “wave” to make graphs interesting.
        $start = Carbon::today()->subDays(59); // inclusive
        $rows  = [];
        foreach ($episodes as $epId) {
            $base = random_int(3, 18);                 // average per-day per-episode
            $amp  = (int) round($base * 0.6);          // wave amplitude
            for ($d = 0; $d < 60; $d++) {
                $date  = (clone $start)->addDays($d);
                // wavy + noise + never negative
                $mult  = sin(($d / 8) * M_PI) + 1.0;    // 0..2
                $count = max(0, (int) round($base * $mult + random_int(-3, 4)));

                // Create N rows for this day for this episode
                for ($i = 0; $i < $count; $i++) {
                    $time = (clone $date)->setTime(random_int(7, 22), random_int(0, 59), random_int(0, 59));
                    $rows[] = [
                        'episode_id' => $epId,
                        'created_at' => $time,
                        'updated_at' => $time,
                    ];
                }
            }
        }

        // Insert in chunks for speed
        foreach (array_chunk($rows, 2000) as $chunk) {
            DB::table('downloads')->insert($chunk);
        }

        // 4) Seed a couple of podcast directory connections for the Distribution page
        if (class_exists(PodcastDirectory::class)) {
            $now = now();
            $upserts = [
                [
                    'user_id'      => $user->id,
                    'slug'         => 'spotify',
                    'external_url' => 'https://open.spotify.com/show/xyz123',
                    'is_connected' => 1,
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ],
                [
                    'user_id'      => $user->id,
                    'slug'         => 'apple',
                    'external_url' => null,
                    'is_connected' => 0,
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ],
            ];

            // idempotent upsert by (user_id, slug)
            foreach ($upserts as $row) {
                PodcastDirectory::updateOrCreate(
                    ['user_id' => $row['user_id'], 'slug' => $row['slug']],
                    $row
                );
            }
        }
    }
}
