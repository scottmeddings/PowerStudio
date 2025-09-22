{{-- resources/views/site/landing_png_hero_episodes_fixedurl.blade.php --}}
@php
  // Hard-set background image as requested
  $hero = 'http://localhost:8000/images/powertime-hero.png';

  // Title & Description (override via $settings if you like)
  $settings = ($settings ?? []) + [
    'title'   => 'Power Time',
    'tagline' => "A podcast exploring low-code development, AI in the enterprise, and practical leadership for high-performing tech teams.",
    // Real links here:
    'apps' => [
      ['name'=>'Apple Podcasts','href'=>'https://podcasts.apple.com/'],
      ['name'=>'Spotify','href'=>'https://open.spotify.com/'],
      ['name'=>'YouTube','href'=>'https://www.youtube.com/'],
      ['name'=>'Amazon Music','href'=>'https://music.amazon.com/podcasts'],
      ['name'=>'Podbean','href'=>'https://www.podbean.com/'],
    ],
  ];

  // Demo episodes if none provided
  if (!isset($episodes)) {
    $episodes = collect([
      (object)['title'=>'The Future of Low-Code','published_at'=>now()->subDays(7),'duration'=>42,'description'=>'Trends and predictions.','url'=>'#'],
      (object)['title'=>'Leading Tech Teams','published_at'=>now()->subMonth(),'duration'=>38,'description'=>'Rituals & coaching.','url'=>'#'],
      (object)['title'=>'Integrating AI','published_at'=>now()->subMonths(2),'duration'=>44,'description'=>'Practical patterns.','url'=>'#'],
    ]);
  }
  $fmtDate = fn($d)=> $d ? \Illuminate\Support\Carbon::parse($d)->isoFormat('MMM D, YYYY') : null;
  $fmtDur  = fn($m)=> $m ? ($m>=60 ? floor($m/60).'h '.($m%60).'m' : $m.'m') : null;
@endphp

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>{{ $settings['title'] }} — Podcast</title>
  <style>
    :root{ --ink:#0b1220; --ink-2:#334155; --ink-3:#64748b; --line:#e6e8ee; --wrap:1100px; --brand:#7c3aed; }
    *{box-sizing:border-box} html,body{margin:0;padding:0}
    body{ background:#000; font:16px/1.6 ui-sans-serif,-apple-system,Segoe UI,Roboto,Inter,Arial,sans-serif; color:var(--ink); }

    .hero{
      min-height:72vh; position:relative;
      background-image:url('{{ $hero }}');
      background-position:center; background-size:cover; background-repeat:no-repeat;
      display:grid; place-items:center; isolation:isolate; padding:24px 22px;
    }
    .hero::after{
      content:""; position:absolute; inset:0; z-index:0;
      background:linear-gradient(180deg, rgba(0,0,0,.25) 0%, rgba(0,0,0,.45) 40%, rgba(0,0,0,.75) 100%);
    }
    .hero-inner{
      position:relative; z-index:1; width:100%; max-width:var(--wrap);
      color:#fff; text-align:center; display:grid; gap:14px; justify-items:center;
    }
    .title{ font-size:56px; line-height:1.05; font-weight:800; letter-spacing:-.02em; margin:0; text-shadow:0 2px 14px rgba(0,0,0,.45) }
    .lede{ color:#e5e7eb; max-width:80ch; margin:0; white-space:pre-line; text-wrap:balance; text-shadow:0 2px 12px rgba(0,0,0,.35) }

    .apps{ display:flex; gap:14px; flex-wrap:wrap; justify-content:center; margin-top:10px }
    .app{
      width:44px; height:44px; border-radius:999px; display:grid; place-items:center;
      background:rgba(255,255,255,.14); border:1px solid rgba(255,255,255,.25); box-shadow:0 2px 8px rgba(0,0,0,.25)
    }
    .app:hover{ background:rgba(255,255,255,.24) }
    .app svg{ width:22px; height:22px; display:block }

    .wrap{ max-width:var(--wrap); margin:0 auto; background:#fff }
    .section{ padding:22px }
    .ep{ padding:18px 0; border-bottom:1px solid var(--line) }
    .ep h3{ margin:4px 0 6px; font-size:1.1rem; line-height:1.25 }
    .meta{ color:var(--ink-3); font-size:.9rem; margin-bottom:6px }
    .desc{ color:var(--ink-2); margin:0 }
    .play{ display:inline-block; background:var(--brand); color:#fff; padding:8px 12px; border-radius:10px; font-weight:700; text-decoration:none }

    @media (max-width: 720px){ .title{ font-size:36px } }
  </style>
</head>
<body>

  <!-- HERO -->
  <header class="hero" role="banner" aria-label="Hero background">
    <div class="hero-inner">
      <h1 class="title">{{ $settings['title'] }}</h1>
      <p class="lede">{{ $settings['tagline'] }}</p>

      <nav class="apps" aria-label="Listen on">
        {{-- Apple Podcasts --}}
        <a class="app" href="{{ $settings['apps'][0]['href'] }}" target="_blank" rel="noopener" title="Apple Podcasts" aria-label="Apple Podcasts">
          <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <path d="M16.365 1.43c-.95.038-2.08.675-2.73 1.47-.6.73-1.13 1.86-.93 2.94.98.08 2.01-.52 2.66-1.32.63-.77 1.12-1.9.99-3.09zm-3.08 4.87c-1.71 0-3.39.99-4.16 2.51-.89 1.71-.77 3.99.3 5.52.6.86 1.61 1.83 2.75 1.8 1.06-.03 1.47-.68 2.76-.68 1.29 0 1.65.68 2.77.66 1.15-.02 1.88-.88 2.49-1.74.78-1.12 1.1-2.19 1.12-2.25-.02-.01-2.17-.83-2.19-3.31-.02-2.06 1.67-3.05 1.75-3.11- .96-1.4-2.47-1.56-3-1.58-1.27-.1-2.33.72-2.94.72-.61 0-1.5-.7-2.56-.66z"/>
          </svg>
        </a>

        {{-- Spotify --}}
        <a class="app" href="{{ $settings['apps'][1]['href'] }}" target="_blank" rel="noopener" title="Spotify" aria-label="Spotify">
          <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <path d="M12 1.5C6.2 1.5 1.5 6.2 1.5 12S6.2 22.5 12 22.5 22.5 17.8 22.5 12 17.8 1.5 12 1.5zm5 15.2c-.2.3-.6.4-.9.2-2.6-1.6-5.9-1.9-9.7-.9-.3.1-.7-.1-.8-.4-.1-.4.1-.7.4-.8 4.2-1.1 7.9-.7 10.8 1.1.3.2.4.6.2.8zm1.3-3c-.3.4-.9.5-1.3.3-3-1.8-7.6-2.4-11.2-1.2-.5.1-1-.2-1.1-.7-.2-.5.2-1 .7-1.2 4.2-1.3 9.3-.6 12.8 1.4.5.2.6.7.1 1.1zm.1-3.2c-3.6-2.1-9.6-2.3-13.1-1.1-.6.2-1.2-.2-1.4-.8-.2-.6.2-1.2.8-1.4 4.1-1.3 10.7-1.1 14.8 1.3.6.3.8 1 .5 1.6-.3.5-1 .7-1.6.4z"/>
          </svg>
        </a>

        {{-- YouTube --}}
        <a class="app" href="{{ $settings['apps'][2]['href'] }}" target="_blank" rel="noopener" title="YouTube" aria-label="YouTube">
          <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <path d="M23.5 6.2s-.2-1.7-.9-2.4c-.9-.9-1.9-.9-2.4-1C17.6 2.5 12 2.5 12 2.5h0s-5.6 0-8.2.3c-.5.1-1.5.1-2.4 1C.8 4.5.5 6.2.5 6.2S.2 8.2.2 10.2v1.6c0 2 .3 4 .3 4s.2 1.7.9 2.4c.9.9 2.1.9 2.7 1 2 .2 8 .3 8 .3s5.6 0 8.2-.3c.5-.1 1.5-.1 2.4-1 .7-.7.9-2.4.9-2.4s.3-2 .3-4v-1.6c0-2-.3-4-.3-4zM9.8 14.6V7.8l6.2 3.4-6.2 3.4z"/>
          </svg>
        </a>

        {{-- Amazon Music --}}
        <a class="app" href="{{ $settings['apps'][3]['href'] }}" target="_blank" rel="noopener" title="Amazon Music" aria-label="Amazon Music">
          <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <path d="M3 17.5c3.3 2 7.2 3 11 3 3.9 0 7.7-1 11-3-.3-.6-1-1-1.7-.7-2.9 1.6-6.4 2.4-9.3 2.4S8.9 18.4 6 16.8c-.7-.4-1.4 0-1.7.7zM10.5 6.5V16c0 .6.4 1 1 1s1-.4 1-1V9.4c.6-.4 1.4-.6 2.3-.6 1.7 0 3.2 1 3.2 3.1V16c0 .6.4 1 1 1s1-.4 1-1v-4.1c0-3-2.1-5.1-5.1-5.1-1.1 0-2.2.3-3.1.9l-.3.2V6.5c0-.6-.4-1-1-1s-1 .4-1 1z"/>
          </svg>
        </a>

        {{-- Podbean --}}
        <a class="app" href="{{ $settings['apps'][4]['href'] }}" target="_blank" rel="noopener" title="Podbean" aria-label="Podbean">
          <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <path d="M12 3.5a8.5 8.5 0 1 0 0 17 8.5 8.5 0 0 0 0-17zm0 2a6.5 6.5 0 1 1 0 13 6.5 6.5 0 0 1 0-13zm0 3.2a3.3 3.3 0 1 0 0 6.6 3.3 3.3 0 0 0 0-6.6z"/>
          </svg>
        </a>
      </nav>
    </div>
  </header>

  <!-- EPISODES -->
  <main class="wrap" role="main">
    <section class="section" aria-labelledby="episodes">
      <div id="episodes" class="meta" style="margin-bottom:10px">All Episodes</div>

      @foreach($episodes as $ep)
        @php
          $date = $fmtDate($ep->published_at ?? ($ep->date ?? null));
          $dur  = $fmtDur($ep->duration ?? null);
          $desc = \Illuminate\Support\Str::limit(strip_tags($ep->description ?? ($ep->desc ?? '')), 180);
          $url  = $ep->url ?? '#';
        @endphp
        <article class="ep">
          <div class="meta">{{ $date }} @if($dur) · {{ $dur }} @endif</div>
          <h3><a href="{{ $url }}" style="color:inherit; text-decoration:none">{{ $ep->title ?? 'Untitled episode' }}</a></h3>
          @if($desc)<p class="desc">{{ $desc }}</p>@endif
          <div style="margin-top:8px"><a class="play" href="{{ $url }}">Play</a></div>
        </article>
      @endforeach
    </section>
  </main>

</body>
</html>
