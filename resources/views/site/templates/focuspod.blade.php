{{-- resources/views/site/podcasts_all_standalone.blade.php --}}
@php
  // ---------- SAFE DEFAULTS ----------
  $settings = ($settings ?? []) + [
    'title'   => config('app.name', 'PowerTime'),
    'brand'   => '#7c3aed',
    'banner'  => null, // storage path or absolute URL
    'layout'  => request('layout', 'grid'), // 'grid' or 'list'
  ];

  // Demo episodes if none provided (kept tiny & safe)
  if (!isset($episodes)) {
    $episodes = collect([
      (object)[ 'title'=>'Welcome to PowerTime', 'slug'=>'welcome', 'published_at'=>now()->subDays(3), 'duration'=>28, 'description'=>'Kick-off episode.', 'image_url'=>null ],
      (object)[ 'title'=>'AI in the Enterprise', 'slug'=>'ai-in-the-enterprise', 'published_at'=>now()->subDays(10), 'duration'=>41, 'description'=>'Practical AI patterns.', 'image_url'=>null ],
    ]);
  }

  // ---------- HELPERS ----------
  $brand = $settings['brand']; if ($brand[0] !== '#') $brand = '#'.$brand;

  $bannerCss = $settings['banner']
    ? 'url('. (str_starts_with($settings['banner'],'http') ? $settings['banner'] : asset('storage/'.$settings['banner'])) .') center/cover no-repeat'
    : 'linear-gradient(135deg, '.$brand.' 0%, '.\Illuminate\Support\Str::of($brand)->replace('#','%23').'33 100%)';

  $isPaginator = is_object($episodes) && method_exists($episodes, 'items') && method_exists($episodes, 'links');

  $all = $isPaginator ? collect($episodes->items()) : collect($episodes);
  $count = $all->count();
  $mins  = (int) round($all->sum(fn($e) => (int)($e->duration ?? 0)));
  $avg   = $count ? (int) round($mins / $count) : 0;
  $latestAt = $all->filter(fn($e)=>!empty($e->published_at))
                  ->max(fn($e)=>\Illuminate\Support\Carbon::parse($e->published_at));

  $fmtDate = fn($d) => $d ? \Illuminate\Support\Carbon::parse($d)->isoFormat('MMM D, YYYY') : null;
  $fmtDur  = function($m){ $m=(int)$m; return $m>=60 ? floor($m/60).'h '.($m%60).'m' : $m.'m'; };
  $urlFor  = function($e){
    if (function_exists('route') && !empty($e->slug)) {
      try { return route('site.episode', $e->slug); } catch (\Throwable $e) {}
    }
    return '#';
  };
  $imgFor  = function($e){
    foreach (['image_url','cover','artwork','image'] as $k) {
      if (!empty($e->{$k})) return $e->{$k};
    }
    return null;
  };

  $layout = in_array($settings['layout'], ['grid','list']) ? $settings['layout'] : 'grid';
@endphp

<!doctype html>
<html lang="en" data-bs-theme="light">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ $settings['title'] }} · All Episodes</title>

  {{-- Bootstrap & Icons --}}
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

  <style>
    :root{
      --brand: {{ $brand }};
      --ink-1:#0b1220; --ink-2:#334155; --ink-3:#64748b; --ink-4:#94a3b8;
      --bg-1:#f6f8fb; --card:#ffffff; --ring:rgba(0,0,0,.08);
      --radius: 16px;
    }
    body{ background:var(--bg-1); color:var(--ink-1); }
    .hero{
      background: {{ $bannerCss }};
      color:#fff; border-bottom:1px solid rgba(255,255,255,.12);
    }
    .hero .shade{ backdrop-filter:saturate(130%) blur(0px); background:linear-gradient(180deg, rgba(0,0,0,.15), rgba(0,0,0,.25)); }
    .stat{ background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.18); border-radius:12px; padding:.75rem 1rem; }
    .toolbar{ background:var(--card); border:1px solid var(--ring); border-radius:var(--radius); padding:.75rem; }

    .grid{ display:grid; grid-template-columns: repeat(12, 1fr); gap:1rem; }
    .col-card{ grid-column: span 12; }
    @media (min-width: 768px){ .col-card{ grid-column: span 6; } }
    @media (min-width: 1200px){ .col-card{ grid-column: span 4; } }

    .card-ep{ background:var(--card); border:1px solid var(--ring); border-radius:var(--radius); overflow:hidden; height:100%; display:flex; flex-direction:column; }
    .card-ep .media{
      aspect-ratio:16/9; background:#e5e7eb; position:relative; overflow:hidden;
      display:grid; place-items:center;
    }
    .card-ep .media img{ width:100%; height:100%; object-fit:cover; }
    .card-ep .badge-date{ position:absolute; left:.75rem; top:.75rem; background:var(--brand); }
    .card-ep .body{ padding:1rem; flex:1; display:flex; flex-direction:column; gap:.5rem; }
    .card-ep .title{ font-weight:700; line-height:1.2; }
    .card-ep .meta{ font-size:.9rem; color:var(--ink-3); }
    .card-ep .desc{ color:var(--ink-2); }
    .card-ep .actions{ display:flex; gap:.5rem; margin-top:auto; }
    .btn-brand{ background:var(--brand); border-color:var(--brand); }
    .btn-ghost{ border:1px solid var(--ring); background:#fff; }

    .list-item{ background:var(--card); border:1px solid var(--ring); border-radius:var(--radius); padding:1rem; display:grid; grid-template-columns:96px 1fr auto; gap:1rem; align-items:center; }
    .thumb{ width:96px; height:96px; border-radius:12px; overflow:hidden; background:#e5e7eb; display:grid; place-items:center; }
    .thumb img{ width:100%; height:100%; object-fit:cover; }
    .list-title{ font-weight:700; margin:0; }
    .list-desc{ color:var(--ink-2); margin:.25rem 0 0; }
    .pill{ font-size:.85rem; color:var(--ink-3); }

    .pagination-wrap{ display:flex; justify-content:center; gap:.5rem; flex-wrap:wrap; }
    .page-link{ border-radius:999px !important; }
    .brand-dot{ width:.6rem; height:.6rem; background:var(--brand); border-radius:999px; display:inline-block; margin-right:.35rem; }

    .form-control:focus, .form-select:focus, .btn:focus{ box-shadow:0 0 0 .25rem rgba(124,58,237,.15); }
  </style>
</head>
<body>

  {{-- HERO --}}
  <section class="hero py-5">
    <div class="shade py-2">
      <div class="container">
        <div class="d-flex flex-wrap align-items-end justify-content-between gap-3">
          <div>
            <div class="d-flex align-items-center gap-2 text-uppercase fw-semibold" style="opacity:.9">
              <span class="brand-dot"></span> Podcast Library
            </div>
            <h1 class="display-5 fw-bold m-0">{{ $settings['title'] }}</h1>
            <p class="m-0 mt-2" style="opacity:.95">
              {{ $count }} episode{{ $count===1?'':'s' }} ·
              {{ $mins }} total mins ·
              avg {{ $fmtDur($avg) }}
              @if($latestAt) · latest {{ $fmtDate($latestAt) }} @endif
            </p>
          </div>

          {{-- Quick stats --}}
          <div class="d-flex gap-2 flex-wrap">
            <div class="stat"><div class="small">Episodes</div><div class="fw-bold">{{ $count }}</div></div>
            <div class="stat"><div class="small">Total</div><div class="fw-bold">{{ $mins }}m</div></div>
            <div class="stat"><div class="small">Average</div><div class="fw-bold">{{ $fmtDur($avg) }}</div></div>
          </div>
        </div>
      </div>
    </div>
  </section>

  {{-- TOOLBAR --}}
  <section class="py-3">
    <div class="container">
      <form class="toolbar d-flex flex-wrap align-items-center gap-2" method="get" action="">
        <div class="flex-grow-1">
          <input type="search" name="q" value="{{ request('q') }}" class="form-control"
                 placeholder="Search episodes by title or description…">
        </div>
        <div>
          <select name="sort" class="form-select">
            @php $sort=request('sort','new'); @endphp
            <option value="new" @selected($sort==='new')>Newest first</option>
            <option value="old" @selected($sort==='old')>Oldest first</option>
            <option value="long" @selected($sort==='long')>Longest first</option>
            <option value="short" @selected($sort==='short')>Shortest first</option>
          </select>
        </div>
        <div class="d-none d-md-block">
          <div class="btn-group" role="group" aria-label="Layout toggle">
            <a class="btn btn-outline-secondary @if($layout==='grid') active @endif"
               href="{{ request()->fullUrlWithQuery(['layout'=>'grid']) }}" title="Grid"><i class="bi bi-grid-3x3-gap"></i></a>
            <a class="btn btn-outline-secondary @if($layout==='list') active @endif"
               href="{{ request()->fullUrlWithQuery(['layout'=>'list']) }}" title="List"><i class="bi bi-list-ul"></i></a>
          </div>
        </div>
        <div>
          <button class="btn btn-brand text-white"><i class="bi bi-search me-1"></i> Apply</button>
        </div>
      </form>
    </div>
  </section>

  {{-- RESULTS --}}
  <section class="pb-5">
    <div class="container">

      @if($count === 0)
        <div class="text-center py-5">
          <div class="display-6">No episodes yet</div>
          <p class="text-muted">Try clearing filters or add your first episode.</p>
        </div>
      @else

        @if($layout === 'grid')
          <div class="grid">
            @foreach($all as $e)
              @php
                $date = $fmtDate($e->published_at ?? null);
                $dur  = $fmtDur($e->duration ?? 0);
                $img  = $imgFor($e);
                $url  = $urlFor($e);
                $desc = \Illuminate\Support\Str::limit(strip_tags($e->description ?? ''), 140);
              @endphp
              <div class="col-card">
                <article class="card-ep">
                  <div class="media">
                    @if($img)
                      <img src="{{ $img }}" alt="">
                    @else
                      <i class="bi bi-soundwave fs-1" style="opacity:.35;"></i>
                    @endif
                    @if($date)<span class="badge text-bg-light badge-date">{{ $date }}</span>@endif
                  </div>
                  <div class="body">
                    <div class="meta">{{ $dur }}</div>
                    <h3 class="title h5 m-0"><a class="link-dark text-decoration-none" href="{{ $url }}">{{ $e->title ?? 'Untitled episode' }}</a></h3>
                    @if($desc)<p class="desc m-0">{{ $desc }}</p>@endif
                    <div class="actions">
                      <a href="{{ $url }}" class="btn btn-brand btn-sm text-white"><i class="bi bi-play-fill me-1"></i> Play</a>
                      <a href="{{ $url }}#share" class="btn btn-ghost btn-sm"><i class="bi bi-share me-1"></i> Share</a>
                    </div>
                  </div>
                </article>
              </div>
            @endforeach
          </div>
        @else
          <div class="vstack gap-3">
            @foreach($all as $e)
              @php
                $date = $fmtDate($e->published_at ?? null);
                $dur  = $fmtDur($e->duration ?? 0);
                $img  = $imgFor($e);
                $url  = $urlFor($e);
                $desc = \Illuminate\Support\Str::limit(strip_tags($e->description ?? ''), 180);
              @endphp
              <article class="list-item">
                <div class="thumb">
                  @if($img) <img src="{{ $img }}" alt=""> @else <i class="bi bi-soundwave fs-3" style="opacity:.35;"></i> @endif
                </div>
                <div>
                  <h3 class="list-title"><a class="link-dark text-decoration-none" href="{{ $url }}">{{ $e->title ?? 'Untitled episode' }}</a></h3>
                  <div class="pill">{{ $date ? $date.' · ' : '' }}{{ $dur }}</div>
                  @if($desc)<p class="list-desc">{{ $desc }}</p>@endif
                </div>
                <div class="d-flex gap-2">
                  <a href="{{ $url }}" class="btn btn-brand text-white"><i class="bi bi-play-fill me-1"></i> Play</a>
                  <a href="{{ $url }}#share" class="btn btn-outline-secondary"><i class="bi bi-share"></i></a>
                </div>
              </article>
            @endforeach
          </div>
        @endif

        {{-- PAGINATION (works for LengthAwarePaginator & Paginator). If not paginated, hides itself. --}}
        @if($isPaginator && $episodes->hasPages())
          <div class="mt-4">
            <nav aria-label="Episodes pages">
              <ul class="pagination justify-content-center">
                {{-- Prev --}}
                <li class="page-item @if($episodes->onFirstPage()) disabled @endif">
                  <a class="page-link" href="{{ $episodes->previousPageUrl() ?? '#' }}" aria-label="Previous">
                    <i class="bi bi-arrow-left"></i>
                  </a>
                </li>

                {{-- Numbers (only for LengthAwarePaginator) --}}
                @if(method_exists($episodes,'lastPage'))
                  @php
                    $current = $episodes->currentPage();
                    $last    = $episodes->lastPage();
                    $window  = 2;
                    $start   = max(1, $current - $window);
                    $end     = min($last, $current + $window);
                  @endphp
                  @if($start > 1)
                    <li class="page-item"><a class="page-link" href="{{ $episodes->url(1) }}">1</a></li>
                    @if($start > 2) <li class="page-item disabled"><span class="page-link">&hellip;</span></li> @endif
                  @endif

                  @for($i=$start; $i<=$end; $i++)
                    <li class="page-item @if($i===$current) active @endif">
                      <a class="page-link" href="{{ $episodes->url($i) }}">{{ $i }}</a>
                    </li>
                  @endfor

                  @if($end < $last)
                    @if($end < $last-1) <li class="page-item disabled"><span class="page-link">&hellip;</span></li> @endif
                    <li class="page-item"><a class="page-link" href="{{ $episodes->url($last) }}">{{ $last }}</a></li>
                  @endif
                @endif

                {{-- Next --}}
                <li class="page-item @if(!$episodes->hasMorePages()) disabled @endif">
                  <a class="page-link" href="{{ $episodes->nextPageUrl() ?? '#' }}" aria-label="Next">
                    <i class="bi bi-arrow-right"></i>
                  </a>
                </li>
              </ul>
            </nav>
          </div>
        @endif

      @endif
    </div>
  </section>

  {{-- JS --}}
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
