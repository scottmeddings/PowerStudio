{{-- resources/views/site/zen_standalone.blade.php --}}
@php
  // ---------- SAFE DEFAULTS ----------
  $settings = ($settings ?? []) + [
    'title'   => config('app.name', 'PowerTime'),
    'brand'   => '#7c3aed',
    'banner'  => null,
    'layout'  => 'list', // 'grid' or 'list'
  ];

  // Demo episodes if none provided
  if (!isset($episodes)) {
    $episodes = collect([
      (object)[ 'title'=>'Welcome to PowerTime', 'slug'=>'welcome', 'published_at'=>now()->subDays(3), 'duration'=>28, 'description'=>'Kick-off episode.' ],
      (object)[ 'title'=>'AI in the Enterprise', 'slug'=>'ai-in-the-enterprise', 'published_at'=>now()->subDays(10), 'duration'=>41, 'description'=>'Practical AI patterns.' ],
    ]);
  }

  // Helpers
  $bannerCss = $settings['banner']
      ? 'url('.asset('storage/'.$settings['banner']).') center/cover no-repeat'
      : 'linear-gradient(135deg, var(--brand), #111)';
@endphp
<!doctype html>
<html lang="en" data-bs-theme="dark">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ $settings['title'] }} · Zen</title>

  {{-- Bootstrap 5.3 --}}
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    :root{
      --brand: {{ $settings['brand'] ?? '#7c3aed' }};
      --bg: #0e0e0f;
      --fg: #e8e8ea;
      --muted: #9aa0a6;
      --card: #151517;
      --surface: #121214;
      --accent: #ef4444; /* play button */
      --ring: color-mix(in oklab, var(--brand) 60%, white);
    }
    html,body { background: var(--bg); color: var(--fg); }

    /* --- Header / Nav --- */
    .zen-topbar {
      border-bottom: 1px solid #232326;
      background: linear-gradient(180deg, rgba(255,255,255,.02), rgba(255,255,255,0));
      backdrop-filter: blur(6px);
    }
    .brand-pill {
      display:inline-flex; align-items:center; gap:.5rem;
      padding:.4rem .8rem; border-radius:999px;
      background: color-mix(in oklab, var(--brand) 18%, black);
      color:#fff; font-weight:600; letter-spacing:.2px;
      box-shadow: inset 0 0 0 1px color-mix(in oklab, var(--brand) 50%, #000);
      text-decoration:none;
    }

    /* --- Hero --- */
    .zen-hero{
      position: relative; overflow: hidden; border-radius: 1rem;
      background:
        linear-gradient(180deg, rgba(0,0,0,.35), rgba(0,0,0,.78)),
        {{ $bannerCss }};
      color:#fff;
      box-shadow: 0 20px 60px rgba(0,0,0,.35), 0 0 0 1px #202024 inset;
    }
    .zen-hero .title{ text-shadow: 0 6px 30px rgba(0,0,0,.45); }
    .zen-hero .listen-on{ opacity:.95 }

    /* phone mock – sits on hero, right side on lg+ */
    .phone-wrap{ position: absolute; right: 2rem; bottom: -2.2rem; display:none; }
    @media (min-width: 992px){ .phone-wrap{ display:block; } }
    .phone{
      width: 320px; aspect-ratio: 9/19.5; border-radius: 40px;
      border: 10px solid #2a2a2c; box-shadow: 0 12px 50px rgba(0,0,0,.6);
      background: rgba(20,20,22,.85); position: relative; overflow: hidden;
    }
    .phone:before{
      content:''; position:absolute; top:8px; left:50%; transform:translateX(-50%);
      width: 30%; height: 10px; background:#1c1c1f; border-radius: 6px;
    }
    .phone .screen{
      position:absolute; inset: 18px 8px 18px; border-radius: 24px;
      background:
        linear-gradient(180deg, rgba(0,0,0,.25), rgba(0,0,0,.6)),
        {{ $bannerCss }};
      display:flex; align-items:flex-end; justify-content:center; padding:12px; color:#fff;
      font-weight:700; font-size: .95rem; letter-spacing:.2px;
      box-shadow: inset 0 0 0 1px rgba(255,255,255,.06);
    }

    /* --- Episode list / cards --- */
    .zen-list{ margin-top: 1.5rem; }
    .zen-ep{
      background: var(--card); border-radius: 1rem; padding: 1rem 1.25rem;
      display:flex; gap: 1rem; align-items:flex-start;
      transition: transform .18s ease, background .18s ease, box-shadow .18s ease;
      box-shadow: 0 1px 0 rgba(255,255,255,.04), 0 10px 30px rgba(0,0,0,.25);
      border: 1px solid #242428;
    }
    .zen-ep:hover{
      transform: translateY(-2px);
      background: #19191c;
      box-shadow: 0 1px 0 rgba(255,255,255,.06), 0 16px 44px rgba(0,0,0,.36);
    }
    .play-btn{
      width: 46px; height: 46px; border-radius: 50%;
      display:grid; place-items:center; flex:0 0 46px;
      background: var(--accent); color:#fff;
      box-shadow: 0 6px 18px rgba(239,68,68,.35), 0 0 0 4px rgba(239,68,68,.12);
      transition: transform .18s ease, box-shadow .18s ease, filter .18s ease;
      border:0;
    }
    .play-btn:hover{
      transform: scale(1.04);
      box-shadow: 0 10px 24px rgba(239,68,68,.45), 0 0 0 6px rgba(239,68,68,.16);
      filter: saturate(1.15);
    }
    .play-btn svg{ width: 16px; height: 16px; margin-left: 2px; }

    .ep-title{ color: var(--fg); text-decoration:none; font-weight:800; letter-spacing:.2px; }
    .ep-title:hover{ color: #fff; text-decoration: underline; text-underline-offset: 3px; }
    .ep-date{ color: var(--muted); font-size: .85rem; }
    .ep-desc{ color:#cfd2d6; margin:.25rem 0 .5rem; line-height:1.55; }
    .ep-meta{ color: var(--muted); font-size:.85rem; display:flex; gap:1rem; align-items:center; flex-wrap:wrap; }
    .ep-meta .dot{ opacity:.6; }

    /* --- Grid cards --- */
    .zen-card{
      background: var(--card); border-radius: 1rem; overflow:hidden;
      border:1px solid #242428; height:100%;
      display:flex; flex-direction:column;
      box-shadow: 0 1px 0 rgba(255,255,255,.04), 0 10px 30px rgba(0,0,0,.25);
    }
    .zen-card .media{
      aspect-ratio: 16/9; background: {{ $bannerCss }};
      border-bottom: 1px solid #242428;
    }
    .zen-card .body{ padding:1rem; }
    .zen-card .title a{ color:#fff; text-decoration:none; font-weight:800; }
    .zen-card .title a:hover{ text-decoration:underline; text-underline-offset:3px; }

    /* pagination theme */
    .page-link{ background:#151517; color:#cfd2d6; border-color:#26262a; }
    .page-item.active .page-link{ background: var(--brand); border-color: var(--brand); }
    .page-link:hover{ color:#fff; background:#1b1b1e; }

    /* subtle focus ring */
    :is(a,button,.play-btn,.brand-pill,.page-link):focus-visible{
      outline: none;
      box-shadow: 0 0 0 3px var(--ring);
    }
  </style>
</head>
<body>

  {{-- Topbar --}}
  <header class="zen-topbar">
    <div class="container-xxl py-3 d-flex justify-content-between align-items-center">
      <a href="{{ url('/') }}" class="brand-pill">
        <span class="d-inline-block rounded-circle" style="width:.75rem;height:.75rem;background:var(--brand)"></span>
        {{ $settings['title'] }}
      </a>
      <nav class="d-none d-md-flex gap-3 small">
        <a class="text-decoration-none text-white-50" href="#">Home</a>
        <a class="text-decoration-none text-white-50" href="#">Subscribe</a>
        <a class="text-decoration-none text-white-50" href="#">Profile</a>
      </nav>
    </div>
  </header>

  {{-- Hero --}}
  <section class="zen-hero p-4 p-md-5 mb-5 mx-3 mx-md-4 mx-lg-0">
    <div class="container-xxl position-relative">
      <div class="row align-items-end">
        <div class="col-lg-7">
          <nav class="small mb-3 opacity-75 d-md-none">
            <a class="text-white-50 me-3" href="#">Home</a>
            <a class="text-white-50 me-3" href="#">Subscribe</a>
            <a class="text-white-50" href="#">Profile</a>
          </nav>

          <h1 class="title display-5 fw-bold mb-2">{{ $settings['title'] }}</h1>
          <p class="lead mb-3">This is a demo podcast site for the new PodPower theme Zen. There are many attractive features such as different theme styles, navigation links, episode layout, player and more.</p>

          <div class="listen-on d-flex align-items-center gap-3 flex-wrap">
            <span class="fw-semibold">Listen on:</span>
            {{-- Inline badges (standalone, no includes) --}}
            <div class="d-flex gap-2 flex-wrap">
              <a class="btn btn-sm btn-light-subtle border" href="#" aria-label="Apple Podcasts">Apple</a>
              <a class="btn btn-sm btn-light-subtle border" href="#" aria-label="Spotify">Spotify</a>
              <a class="btn btn-sm btn-light-subtle border" href="#" aria-label="YouTube Music">YT Music</a>
              <a class="btn btn-sm btn-light-subtle border" href="#" aria-label="Amazon Music">Amazon</a>
            </div>
          </div>
        </div>
      </div>

      <div class="phone-wrap">
        <div class="phone">
          <div class="screen">
            <div>Now Playing · Zen</div>
          </div>
        </div>
      </div>
    </div>
  </section>

  {{-- Episodes --}}
  <section class="container-xxl mb-5">
    @if(($settings['layout'] ?? 'list') === 'grid')
      <div class="row g-3">
        @foreach($episodes as $ep)
          @php
            $date = isset($ep->published_at) && $ep->published_at
              ? \Illuminate\Support\Carbon::parse($ep->published_at)->isoFormat('MMM D, YYYY')
              : null;
            $desc = \Illuminate\Support\Str::limit(strip_tags($ep->description ?? ''), 140);
            $url  = function_exists('route') ? route('site.episode', $ep->slug) : '#';
          @endphp
          <div class="col-12 col-md-6 col-xl-4">
            <article class="zen-card">
              <div class="media"></div>
              <div class="body">
                <div class="text-secondary small mb-1">{{ $date ?: 'Unscheduled' }}</div>
                <h3 class="h5 title mb-2"><a href="{{ $url }}">{{ $ep->title ?? 'Untitled episode' }}</a></h3>
                @if($desc)<p class="mb-3 text-body-secondary">{{ $desc }}</p>@endif
                <div class="d-flex justify-content-between align-items-center">
                  <div class="text-secondary small">{{ ($ep->duration ?? null) ? $ep->duration.' min' : '—' }}</div>
                  <a href="{{ $url }}" class="btn btn-sm btn-primary" style="--bs-btn-bg:var(--brand);--bs-btn-border-color:var(--brand);">Open</a>
                </div>
              </div>
            </article>
          </div>
        @endforeach
      </div>
    @else
      <div class="zen-list">
        @foreach($episodes as $ep)
          @php
            $date = isset($ep->published_at) && $ep->published_at
              ? \Illuminate\Support\Carbon::parse($ep->published_at)->isoFormat('dddd MMM D, YYYY')
              : null;
            $dur  = $ep->duration ?? null;
            $desc = \Illuminate\Support\Str::limit(strip_tags($ep->description ?? ''), 180);
            $url  = function_exists('route') ? route('site.episode', $ep->slug) : '#';
          @endphp
          <article class="zen-ep mb-3">
            <a href="{{ $url }}" class="play-btn" aria-label="Play {{ $ep->title }}">
              <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M8 5v14l11-7z"/>
              </svg>
            </a>
            <div class="flex-grow-1">
              <div class="ep-date">{{ $date ?: 'Unscheduled' }}</div>
              <h3 class="h5 mb-1"><a class="ep-title" href="{{ $url }}">{{ $ep->title ?? 'Untitled episode' }}</a></h3>
              @if($desc)<p class="ep-desc mb-2">{{ $desc }}</p>@endif
              <div class="ep-meta">
                @if($dur)<span>{{ $dur }} min</span><span class="dot">•</span>@endif
                <span>Likes</span><span class="dot">•</span>
                <span>Downloads</span><span class="dot">•</span>
                <span>Share</span>
              </div>
            </div>
          </article>
        @endforeach
      </div>
    @endif

    {{-- Pagination (supports paginator or simple collection) --}}
    <div class="mt-4">
      @if(method_exists($episodes, 'links'))
        {{-- Try Bootstrap 5 renderer if installed; fallback to default --}}
        @if(View::exists('pagination::bootstrap-5'))
          {!! $episodes->links('pagination::bootstrap-5') !!}
        @else
          {!! $episodes->links() !!}
        @endif
      @endif
    </div>
  </section>

  <footer class="py-5 border-top border-dark-subtle">
    <div class="container-xxl small text-secondary">
      © {{ date('Y') }} {{ $settings['title'] }} · Built with Zen
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
