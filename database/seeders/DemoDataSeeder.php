<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // ── User ────────────────────────────────────────────────────────────────
        $user = DB::table('users')->where('email', 'demo@powerpod.local')->first();
        if (!$user) {
            $userId = DB::table('users')->insertGetId([
                'name'       => 'Powerpod Demo',
                'email'      => 'demo@powerpod.local',
                'password'   => Hash::make('password'),  // login: demo@powerpod.local / password
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $user = DB::table('users')->find($userId);
        }

        // ── Episodes ───────────────────────────────────────────────────────────
        $titles = [
            "E19: CVP Power Platform Ryan Cunningham chat to Matt and Scott about the Platform and AI",
            "Special Edition – Kids Helpline’s Tracy Adams on the 24/7 Campaign and the Contact Centre",
            "Building Your First Audience: From Zero to 1,000 Listeners",
            "Monetization Playbook: Ads vs. Memberships",
            "How to Edit Faster: Pro Tips for Podcasters",
            "Scaling Production with AI Tools in 2025",
            "Interviews that Don’t Suck: Question Design",
            "Studio Acoustics on a Budget",
            "From Podcast to YouTube: Repurposing Tactics",
            "The Big Roundup: Your Questions Answered",
        ];

        $episodeIds = [];
        $start = Carbon::now()->subDays(70);
        foreach ($titles as $i => $title) {
            $publishedAt = (clone $start)->addDays($i * 6 + rand(0, 2))->setTime(rand(8, 18), rand(0, 59));
            $id = DB::table('episodes')->insertGetId([
                'user_id'          => $user->id,
                'title'            => $title,
                'slug'             => Str::slug($title) . '-' . Str::random(5),
                'description'      => fake()->paragraph(3),
                'audio_url'        => 'https://cdn.example.com/audio/' . Str::uuid() . '.mp3',
                'duration_seconds' => rand(1400, 3600),
                'status'           => 'published',
                'published_at'     => $publishedAt,
                'downloads_count'  => 0,
                'comments_count'   => 0,
                'created_at'       => $publishedAt->copy()->subDay(),
                'updated_at'       => now(),
            ]);
            $episodeIds[] = $id;
        }

        // ── Downloads for last 30 days (time-series events) ────────────────────
        $sources  = ['web', 'apple', 'spotify', 'overcast', 'pocketcasts'];
        $countries = ['US','AU','GB','CA','DE','NZ'];
        $now = Carbon::now();
        $rows = [];

        // Rough daily baseline with some waves so the chart looks alive
        for ($d = 30; $d >= 0; $d--) {
            $day = $now->copy()->subDays($d);
            // Baseline varies; weekends lower
            $baseline = 20 + (int)(10 * sin($d / 4)) + (in_array($day->dayOfWeekIso, [6,7]) ? -8 : 0);
            $baseline = max(3, $baseline);

            foreach ($episodeIds as $epId) {
                // Weight downloads toward the 3 most recent episodes
                $recentBoost = in_array($epId, array_slice(array_reverse($episodeIds), 0, 3)) ? 1.7 : 1.0;
                $count = (int) round($recentBoost * $baseline * (0.6 + lcg_value())); // add randomness

                for ($i = 0; $i < $count; $i++) {
                    $rows[] = [
                        'episode_id' => $epId,
                        'source'     => $sources[array_rand($sources)],
                        'country'    => $countries[array_rand($countries)],
                        'ip'         => long2ip(rand(0, "4294967295")),
                        'user_agent' => fake()->userAgent(),
                        'created_at' => $day->copy()->setTime(rand(0, 23), rand(0, 59), rand(0, 59)),
                        'updated_at' => $now,
                    ];
                }
            }
        }

        // Batch insert downloads in chunks to avoid memory issues
        foreach (array_chunk($rows, 1000) as $chunk) {
            DB::table('downloads')->insert($chunk);
        }

        // Update per-episode counters
        $counts = DB::table('downloads')
            ->select('episode_id', DB::raw('COUNT(*) as c'))
            ->groupBy('episode_id')->pluck('c', 'episode_id');

        foreach ($counts as $epId => $c) {
            DB::table('episodes')->where('id', $epId)->update(['downloads_count' => $c]);
        }

        // ── Comments ───────────────────────────────────────────────────────────
        $commentBodies = [
            "Loved this episode—great pacing and insights.",
            "The AI section was 🔥 Thanks for sharing!",
            "Can you do a follow-up on editing workflows?",
            "Audio quality is crisp, keep it up!",
            "I learned a ton—subscribed.",
        ];

        $commentRows = [];
        foreach ($episodeIds as $epId) {
            $n = rand(0, 3);
            for ($i = 0; $i < $n; $i++) {
                $commentRows[] = [
                    'episode_id' => $epId,
                    'user_id'    => null,
                    'author_name'=> fake()->name(),
                    'author_email'=> fake()->safeEmail(),
                    'body'       => $commentBodies[array_rand($commentBodies)],
                    'approved'   => true,
                    'created_at' => now()->subDays(rand(0, 20)),
                    'updated_at' => now(),
                ];
            }
            DB::table('episodes')->where('id', $epId)->increment('comments_count', $n);
        }
        if ($commentRows) {
            DB::table('episode_comments')->insert($commentRows);
        }

        // ── Achievements (example) ─────────────────────────────────────────────
        DB::table('achievement_unlocks')->updateOrInsert(
            ['user_id' => $user->id, 'code' => 'downloads_2000'],
            ['title' => '2,000 Downloads', 'description' => 'You reached 2,000 total downloads!', 'unlocked_at' => now(), 'updated_at' => now()]
        );

        DB::table('achievement_unlocks')->updateOrInsert(
            ['user_id' => $user->id, 'code' => 'episodes_10'],
            ['title' => '10 Episodes Published', 'description' => 'You have published 10 episodes.', 'unlocked_at' => now(), 'updated_at' => now()]
        );

        $this->command->info('Demo data seeded. Login: demo@powerpod.local / password');
    }
}
