<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Episode;
use App\Models\Download;
use App\Models\Comment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

// Optional — only if you created this model/table
use App\Models\PodcastDirectory;

class PowerTimeSeeder extends Seeder
{
    public function run(): void
    {
        // Use small insert chunks for SQLite (param cap ~999)
        $driver    = DB::getDriverName();
        $chunkSize = $driver === 'sqlite' ? 150 : 2000;

        DB::transaction(function () use ($chunkSize) {
            // 1) Host user
            $host = User::query()->firstOrCreate(
                ['email' => 'demo@powertime.au'],
                [
                    'name'              => 'PowerTime AU',
                    'password'          => bcrypt('password'),
                    'email_verified_at' => now(),
                ]
            );

            // 2) Episodes
            $episodesData = [
                ['E19: CVP Power Platform — Ryan Cunningham on the Platform & AI',
                 'Ryan joins to break down what’s new across Power Platform, how copilots change app building, and the road ahead for makers.'],
                ['E18: Azure OpenAI in the enterprise — patterns, pitfalls, & pricing',
                 'We dig into reference architectures, prompt flows, retrieval augmentation, and cost control for production workloads.'],
                ['E17: Microsoft Fabric — end-to-end analytics or nice marketing?',
                 'Data engineers and analysts share hands-on takes on Fabric, OneLake, and when to adopt vs. wait.'],
                ['E16: Copilot for Power BI — better visuals or better thinking?',
                 'Report authors talk about what Copilot actually accelerates, and where it falls short today.'],
                ['E15: Low-code governance — center of excellence that actually works',
                 'Scaling makers without creating a shadow-IT mess. Policies, DLP, and design systems that stick.'],
                ['E14: Azure API Management for citizen devs',
                 'Bridging pro-dev APIs and low-code consumers. Rate limits, productization, and developer portals.'],
                ['E13: Real-world Dataverse performance tuning',
                 'Indexes, solutions, and query patterns that keep apps snappy at scale.'],
                ['E12: App modernisation — what to refactor and what to retire',
                 'Portfolio maps, strangler fig patterns, and winning quick wins with Power Apps.'],
                ['E11: From VBA hell to Power Platform',
                 'War stories from replacing legacy Excel + Access solutions with maintainable apps & flows.'],
                ['E10: AI safety for internal copilots',
                 'Guardrails, evaluations, and getting legal/security to yes.'],
                ['E09: Power Automate at 10k runs/day',
                 'Queues, concurrency, and robust error handling in the real world.'],
                ['E08: The truth about licensing (and how to optimise it)',
                 'How teams avoid surprises and forecast spend as adoption grows.'],
            ];

            $publishStart = Carbon::now()->subWeeks(count($episodesData) - 1)->startOfWeek();
            $episodeIds   = [];

            foreach ($episodesData as $i => [$title, $desc]) {
                $slugBase = Str::slug($title) ?: 'episode';
                $slug     = $slugBase;
                $j        = 1;
                while (Episode::where('slug', $slug)->exists()) {
                    $slug = $slugBase.'-'.$j++;
                }

                $publishedAt = (clone $publishStart)->addWeeks($i)->setTime(rand(7, 10), rand(0, 59));
                $duration    = rand(24, 62) * 60;

                $ep = Episode::create([
                    'user_id'          => $host->id,
                    'title'            => $title,
                    'slug'             => $slug,
                    'description'      => $desc,
                    'audio_url'        => 'https://cdn.example.com/powertime/'.Str::slug($title).'.mp3',
                    'duration_seconds' => $duration,
                    'status'           => 'published',
                    'published_at'     => $publishedAt,
                ]);

                $episodeIds[] = $ep->id;
            }

            // 3) Downloads over last 90 days (ensure recent activity)
            $now   = now();
            $start = (clone $now)->subDays(89)->startOfDay();
            $ua    = [
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/123.0 Safari/537.36',
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 Version/16.3 Safari/605.1.15',
                'PocketCasts/7.40 (iOS 17.4; iPhone15,2)',
                'Spotify/8.9.4 iOS/17.4',
                'Overcast/2024.5 (iOS 17.1)',
                'Castbox/10.3 Android/13',
            ];

            // Weight newer episodes slightly higher
            $weights = [];
            foreach ($episodeIds as $idx => $id) {
                $weights[$id] = 1.0 + ($idx / max(1, count($episodeIds) - 1)) * 1.2; // 1.0..~2.2
            }

            $rows = [];
            for ($d = 0; $d < 90; $d++) {
                $day  = (clone $start)->addDays($d);
                $base = 36 + (int)(20 * sin($d / 3.0)) + rand(0, 18); // weekly-ish seasonality

                // extra boost for the most recent 7 days + a little for yesterday
                if ($day->greaterThanOrEqualTo($now->copy()->subDays(6)->startOfDay())) {
                    $base += 22;
                }
                if ($day->isSameDay($now->copy()->subDay())) {
                    $base += 15;
                }

                foreach ($episodeIds as $pos => $epId) {
                    $pub = (clone $publishStart)->addWeeks($pos);
                    $daysSincePub = $pub->diffInDays($day, false);

                    // launch week bump then slow decay
                    $launch = $daysSincePub >= 0 && $daysSincePub <= 7 ? 1.8 : exp(-max($daysSincePub, 0) / 45);
                    $expected = max(0, (int) round($base * $weights[$epId] * $launch / max(1, count($episodeIds))));

                    for ($i = 0; $i < $expected; $i++) {
                        $rows[] = [
                            'episode_id' => $epId,
                            'user_agent' => $ua[array_rand($ua)],
                            'ip_address' => long2ip(random_int(0x0B000000, 0xDF000000)),
                            'created_at' => $day->copy()->setTime(rand(0, 23), rand(0, 59), rand(0, 59)),
                            'updated_at' => $now,
                        ];

                        if (count($rows) >= $chunkSize) {
                            DB::table('downloads')->insert($rows);
                            $rows = [];
                        }
                    }
                }
            }
            if ($rows) {
                DB::table('downloads')->insert($rows);
            }

            // 4) Recompute per-episode cached counts (if you use this field anywhere)
            foreach ($episodeIds as $epId) {
                $cnt = Download::where('episode_id', $epId)->count();
                Episode::where('id', $epId)->update(['downloads_count' => $cnt]);
            }

            // 5) A few approved comments on recent episodes
            $people = [
                ['Sam',   'Loved the concrete examples of prompt flows—very actionable.'],
                ['Alex',  'The governance section hit home. More on DLP templates please!'],
                ['Priya', 'We just rolled out Copilot in BI—totally agree with the “better thinking” framing.'],
                ['Ben',   'Could you share a reference architecture for Fabric + Dataverse?'],
                ['Kim',   'Great episode. Licensing tips saved us real money.'],
            ];

            $recent = Episode::whereIn('id', $episodeIds)
                ->orderByDesc('published_at')
                ->take(4)->get();

            foreach ($recent as $ep) {
                $count = rand(2, 5);
                for ($i = 0; $i < $count; $i++) {
                    [$name, $body] = $people[array_rand($people)];
                    $user = User::firstOrCreate(
                        ['email' => Str::slug($name).($i+1).'@example.test'],
                        ['name' => $name, 'password' => bcrypt('password'), 'email_verified_at' => now()]
                    );

                    Comment::create([
                        'episode_id' => $ep->id,
                        'user_id'    => $user->id,
                        'body'       => $body,
                        'status'     => 'approved',
                        'created_at' => $ep->published_at->copy()->addDays(rand(0, 10))->setTime(rand(8, 22), rand(0, 59)),
                        'updated_at' => now(),
                    ]);
                }
            }

            // 6) Mark Spotify connected (if you have this table)
            if (class_exists(PodcastDirectory::class)) {
                PodcastDirectory::updateOrCreate(
                    ['user_id' => $host->id, 'slug' => 'spotify'],
                    ['external_url' => 'https://open.spotify.com/show/demo-show', 'is_connected' => true]
                );
            }
        });
    }
}
