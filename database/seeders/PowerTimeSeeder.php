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

class PowerTimeSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // 1) Host user for the show
            $host = User::query()->firstOrCreate(
                ['email' => 'demo@powertime.au'],
                [
                    'name'              => 'PowerTime AU',
                    'password'          => bcrypt('password'), // change in prod
                    'email_verified_at' => now(),
                    // If you added a cover_path to users, you can set one here:
                    // 'cover_path' => null,
                ]
            );

            // 2) Canonical episode list (realistic titles/descriptions)
            $episodes = [
                [
                    'title' => 'E19: CVP Power Platform — Ryan Cunningham on the Platform & AI',
                    'desc'  => 'Ryan joins to break down what’s new across Power Platform, how copilots change app building, and the road ahead for makers.',
                ],
                [
                    'title' => 'E18: Azure OpenAI in the enterprise — patterns, pitfalls, & pricing',
                    'desc'  => 'We dig into reference architectures, prompt flows, retrieval augmentation, and cost control for production workloads.',
                ],
                [
                    'title' => 'E17: Microsoft Fabric — end-to-end analytics or nice marketing?',
                    'desc'  => 'Data engineers and analysts share hands-on takes on Fabric, OneLake, and when to adopt vs. wait.',
                ],
                [
                    'title' => 'E16: Copilot for Power BI — better visuals or better thinking?',
                    'desc'  => 'Report authors talk about what Copilot actually accelerates, and where it falls short today.',
                ],
                [
                    'title' => 'E15: Low-code governance — center of excellence that actually works',
                    'desc'  => 'Scaling makers without creating a shadow-IT mess. Policies, DLP, and design systems that stick.',
                ],
                [
                    'title' => 'E14: Azure API Management for citizen devs',
                    'desc'  => 'Bridging pro-dev APIs and low-code consumers. Rate limits, productization, and developer portals.',
                ],
                [
                    'title' => 'E13: Real-world Dataverse performance tuning',
                    'desc'  => 'Indexes, solutions, and query patterns that keep apps snappy at scale.',
                ],
                [
                    'title' => 'E12: App modernisation — what to refactor and what to retire',
                    'desc'  => 'Portfolio maps, strangler fig patterns, and winning quick wins with Power Apps.',
                ],
                [
                    'title' => 'E11: From VBA hell to Power Platform',
                    'desc'  => 'War stories from replacing legacy Excel + Access solutions with maintainable apps & flows.',
                ],
                [
                    'title' => 'E10: AI safety for internal copilots',
                    'desc'  => 'Guardrails, evaluations, and getting legal/security to yes.',
                ],
                [
                    'title' => 'E09: Power Automate at 10k runs/day',
                    'desc'  => 'Queues, concurrency, and robust error handling in the real world.',
                ],
                [
                    'title' => 'E08: The truth about licensing (and how to optimise it)',
                    'desc'  => 'How teams avoid surprises and forecast spend as adoption grows.',
                ],
            ];

            // Choose start date (publish weekly cadence)
            $publishStart = Carbon::now()->subWeeks(count($episodes) - 1)->startOfWeek();

            // 3) Insert episodes (published), with realistic durations
            $episodeIds = [];
            foreach ($episodes as $i => $e) {
                $title = $e['title'];
                $slugBase = Str::slug($title) ?: 'episode';
                $slug = $slugBase;
                $j = 1;
                while (Episode::where('slug', $slug)->exists()) {
                    $slug = $slugBase.'-'.$j++;
                }

                $publishedAt = (clone $publishStart)->addWeeks($i)->setTime(rand(7, 10), rand(0, 59)); // mornings AU time
                $duration = rand(24, 62) * 60; // 24–62 minutes

                $ep = Episode::create([
                    'user_id'          => $host->id,
                    'title'            => $title,
                    'slug'             => $slug,
                    'description'      => $e['desc'],
                    'audio_url'        => 'https://cdn.example.com/powertime/'.Str::slug($title).'.mp3',
                    'duration_seconds' => $duration,
                    'status'           => 'published',
                    'published_at'     => $publishedAt,
                    // If you added per-episode covers, you can set cover_path/null here
                    // 'cover_path'       => null,
                ]);

                $episodeIds[] = $ep->id;
            }

            // 4) Generate downloads for last 90 days with a “newer episodes trend”
            //    Newer episodes get a slightly higher weight so they look “hot”.
            $weights = [];
            foreach ($episodeIds as $idx => $id) {
                $weights[$id] = 1.0 + ($idx / max(1, count($episodeIds) - 1)) * 1.2; // 1.0 .. ~2.2
            }

            $userAgents = [
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/123.0 Safari/537.36',
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 Version/16.3 Safari/605.1.15',
                'PocketCasts/7.40 (iOS 17.4; iPhone15,2)',
                'Spotify/8.9.4 iOS/17.4',
                'Overcast/2024.5 (iOS 17.1)',
                'Google-Podcasts/2.0 Android/14',
                'Castbox/10.3 Android/13',
            ];

            $now = Carbon::now();
            $start = (clone $now)->subDays(89)->startOfDay(); // 90 days incl today
            $downloadRows = [];
            $days = 90;

            for ($d = 0; $d < $days; $d++) {
                $day = (clone $start)->addDays($d);
                // Base demand with a weekly seasonality (peaks Tue/Wed)
                $base = 40 + (int)(25 * sin($d / 3.0)) + rand(0, 20);

                foreach ($episodeIds as $epId) {
                    // More downloads near publish week; decay afterwards
                    $epIndex = array_search($epId, $episodeIds, true);
                    $epPublished = (clone $publishStart)->addWeeks($epIndex);
                    $daysSincePub = $epPublished->diffInDays($day, false);

                    // “launch week” boost then exponential decay
                    $launchBoost = $daysSincePub >= 0 && $daysSincePub <= 7 ? 1.8 : exp(-max($daysSincePub, 0) / 45);

                    $expected = max(0, (int) round($base * $weights[$epId] * $launchBoost / count($episodeIds)));

                    for ($i = 0; $i < $expected; $i++) {
                        $downloadRows[] = [
                            'episode_id' => $epId,
                            'user_agent' => $userAgents[array_rand($userAgents)],
                            'ip_address' => long2ip(random_int(0x0B000000, 0xDF000000)), // random-ish private/public-ish range
                            'created_at' => $day->copy()->setTime(rand(0, 23), rand(0, 59), rand(0, 59)),
                            'updated_at' => $now,
                        ];
                    }
                }

                // Insert in chunks to keep memory sane
                if (count($downloadRows) > 5000) {
                    DB::table('downloads')->insert(array_splice($downloadRows, 0, 5000));
                }
            }
            if (count($downloadRows)) {
                DB::table('downloads')->insert($downloadRows);
            }

            // 5) A few comments on recent episodes
            $people = [
                ['name' => 'Sam',   'body' => 'Loved the concrete examples of prompt flows—very actionable.'],
                ['name' => 'Alex',  'body' => 'The governance section hit home. More on DLP templates please!'],
                ['name' => 'Priya', 'body' => 'We just rolled out Copilot in BI—totally agree with the “better thinking” framing.'],
                ['name' => 'Ben',   'body' => 'Could you share a reference architecture for Fabric + Dataverse?'],
                ['name' => 'Kim',   'body' => 'Great episode. Licensing tips saved us real money.'],
            ];

            $recent = Episode::query()
                ->whereIn('id', $episodeIds)
                ->orderByDesc('published_at')
                ->take(4)->get();

            foreach ($recent as $ep) {
                $count = rand(2, 5);
                for ($i = 0; $i < $count; $i++) {
                    $p = $people[array_rand($people)];
                    $user = User::firstOrCreate(
                        ['email' => Str::slug($p['name']).($i+1).'@example.test'],
                        ['name' => $p['name'], 'password' => bcrypt('password'), 'email_verified_at' => now()]
                    );

                    Comment::create([
                        'episode_id' => $ep->id,
                        'user_id'    => $user->id,
                        'body'       => $p['body'],
                        'status'     => 'approved',
                        'created_at' => $ep->published_at->copy()->addDays(rand(0, 10))->setTime(rand(8, 22), rand(0, 59)),
                        'updated_at' => now(),
                    ]);
                }
            }
        });
    }
}
