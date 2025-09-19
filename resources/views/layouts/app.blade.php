{{-- resources/views/layouts/app.blade.php --}}
<!doctype html>
<html lang="{{ str_replace('_','-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Powerpod · @yield('title', 'Dashboard')</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

  <style>
    :root{ --brand-1:#6366f1; --brand-2:#06b6d4; --sidebar-w:260px; }
    body{ background-color:#f5f7fb; }
    .modal{ z-index:3000 } .modal-backdrop{ z-index:2990 }

    .app{
      min-height:100vh; display:grid;
      grid-template-columns:var(--sidebar-w) 1fr;
      grid-template-rows:auto 1fr;
      grid-template-areas:"sidebar topbar" "sidebar main";
    }
    .sidebar{
      grid-area:sidebar; background:#0f172a; color:#cbd5e1;
      position:sticky; top:0; height:100vh; padding:1rem 0; z-index:1030;
    }
    .topbar{
      grid-area:topbar; background:#fff; border-bottom:1px solid rgba(0,0,0,.06);
      position:sticky; top:0; z-index:1010;
    }
    .main{ grid-area:main; position:relative; z-index:1; }

    /* Sidebar UI */
    .sidebar .brand{
      display:flex; align-items:center; gap:.75rem;
      padding:.5rem 1.25rem 1rem; color:#fff; text-decoration:none;
    }
    .brand-badge{
      width:34px; height:34px; border-radius:10px;
      display:inline-grid; place-items:center; color:#fff;
      background:linear-gradient(135deg,var(--brand-1),var(--brand-2));
      box-shadow:0 10px 20px rgba(99,102,241,.35);
    }
    .sidebar .nav-link{ color:#cbd5e1; border-radius:.5rem; padding:.6rem 1rem; margin:.2rem .75rem; }
    .sidebar .nav-link.active,.sidebar .nav-link:hover{ color:#fff; background:rgba(255,255,255,.08); }
    .sidebar .collapse .nav-link.active{ background:rgba(255,255,255,.06); color:#fff; }
    .sidebar .nav-link.ps-4{ font-size:.95rem; opacity:.95; }

    .section-card{ background:#fff; border:1px solid rgba(0,0,0,.06); border-radius:.75rem; box-shadow:0 10px 30px rgba(0,0,0,.03); }
    .btn-blush{ color:#fff; background:linear-gradient(135deg,#fb7185,#f472b6); border-color:#ec4899; box-shadow:0 .35rem 1rem rgba(236,72,153,.25)}
    .btn-blush:hover{ color:#fff; background:linear-gradient(135deg,#db2777,#ec4899); border-color:#db2777 }
    .btn-outline-blush{ color:#ec4899; border-color:#ec4899 }
    .btn-outline-blush:hover{ color:#fff; background:#ec4899; border-color:#ec4899 }
    i.bi, .bi, svg.bi{ font-size:1rem; line-height:1 }

    /* Mobile sidebar */
    @media (max-width:992px){
      .app{ grid-template-columns:1fr; grid-template-areas:"topbar" "main" }
      .sidebar{ position:fixed; inset:0 auto 0 0; width:var(--sidebar-w); transform:translateX(-100%); transition:transform .25s; z-index:1050 }
      .sidebar.show{ transform:translateX(0) }
      .sidebar-backdrop{ display:none; position:fixed; inset:0; background:rgba(0,0,0,.35); z-index:1049 }
      .sidebar-backdrop.show{ display:block }
    }
  </style>

  @stack('styles')
</head>
<body>
<div class="app">
  {{-- Sidebar --}}
  <aside id="sidebar" class="sidebar">
    <a href="{{ route('dashboard') }}" class="brand">
      <span class="brand-badge"><i class="bi bi-soundwave"></i></span>
      <span class="fw-semibold">Powerpod</span>
    </a>

    <nav class="mt-2">
      <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
         href="{{ route('dashboard') }}" @if(request()->routeIs('dashboard')) aria-current="page" @endif>
        <i class="bi bi-speedometer2 me-2"></i>Dashboard
      </a>

      <a class="nav-link {{ request()->routeIs('episodes*') ? 'active' : '' }}"
         href="{{ route('episodes') }}" @if(request()->routeIs('episodes*')) aria-current="page" @endif>
        <i class="bi bi-mic me-2"></i>Episodes
      </a>

      {{-- Distribution dropdown --}}
      @php
        $inDistribution = request()->routeIs('distribution*');

        $distIndex  = route('distribution');

        // Use Route::has(...) to check existence of named routes
        $appsUrl    = \Route::has('distribution.apps')    ? route('distribution.apps')    : $distIndex;
        $socialUrl  = \Route::has('distribution.social')  ? route('distribution.social')  : $distIndex;
        $websiteUrl = \Route::has('distribution.website') ? route('distribution.website') : $distIndex;
        $playerUrl  = \Route::has('distribution.player')  ? route('distribution.player')  : $distIndex;
      @endphp
      <div class="mt-2">
        <div class="d-flex align-items-center justify-content-between mx-2">
          <a class="nav-link flex-grow-1 {{ $inDistribution ? 'active' : '' }}" href="">
            <i class="bi bi-broadcast-pin me-2"></i>Distribution
          </a>
          <button class="btn btn-sm btn-outline-secondary ms-2"
                  type="button"
                  data-bs-toggle="collapse"
                  data-bs-target="#distributionMenu"
                  aria-controls="distributionMenu"
                  aria-expanded="{{ $inDistribution ? 'true' : 'false' }}">
            <i class="bi bi-chevron-down"></i>
          </button>
        </div>

        <div id="distributionMenu" class="collapse {{ $inDistribution ? 'show' : '' }}">
          <ul class="list-unstyled my-2">
            <li>
              <a class="nav-link ps-4 {{ request()->routeIs('distribution.apps') ? 'active' : '' }}"
                 href="{{ $appsUrl }}">
                <i class="bi bi-app-indicator me-2"></i>Podcast Apps
                <span class="ms-2 align-middle" style="color:#ef4444;">•</span>
              </a>
            </li>
            <li>
              <a class="nav-link ps-4 {{ request()->routeIs('distribution.social') ? 'active' : '' }}"
                 href="{{ $socialUrl }}">
                <i class="bi bi-share me-2"></i>Social Share
              </a>
            </li>
            <li>
              <a class="nav-link ps-4 {{ request()->routeIs('distribution.website') ? 'active' : '' }}"
                 href="{{ $websiteUrl }}">
                <i class="bi bi-globe2 me-2"></i>Podcast Website
              </a>
            </li>
            <li>
              <a class="nav-link ps-4 {{ request()->routeIs('distribution.player') ? 'active' : '' }}"
                 href="{{ $playerUrl }}">
                <i class="bi bi-play-btn me-2"></i>Embeddable Player
              </a>
            </li>
          </ul>
        </div>
      </div>

      <a class="nav-link {{ request()->routeIs('statistics*') ? 'active' : '' }}"
         href="{{ route('statistics') }}" @if(request()->routeIs('statistics*')) aria-current="page" @endif>
        <i class="bi bi-graph-up-arrow me-2"></i>Statistics
      </a>

      <a class="nav-link {{ request()->routeIs('monetization*') ? 'active' : '' }}"
         href="{{ route('monetization') }}" @if(request()->routeIs('monetization*')) aria-current="page" @endif>
        <i class="bi bi-currency-dollar me-2"></i>Monetization
      </a>

      {{-- Settings dropdown --}}
      @php $inSettings = request()->routeIs('settings.*'); @endphp
      <div class="mt-2">
        <div class="d-flex align-items-center justify-content-between mx-2">
          <a class="nav-link flex-grow-1 {{ $inSettings ? 'active' : '' }}"
             href="{{ route('settings.index') }}">
            <i class="bi bi-gear me-2"></i>Settings
          </a>
          <button class="btn btn-sm btn-outline-secondary ms-2"
                  type="button"
                  data-bs-toggle="collapse"
                  data-bs-target="#settingsMenu"
                  aria-controls="settingsMenu"
                  aria-expanded="{{ $inSettings ? 'true' : 'false' }}">
            <i class="bi bi-chevron-down"></i>
          </button>
        </div>

        <div id="settingsMenu" class="collapse {{ $inSettings ? 'show' : '' }}">
          <ul class="list-unstyled my-2">
            <li>
              <a class="nav-link ps-4 {{ request()->routeIs('settings.general') ? 'active' : '' }}"
                 href="{{ route('settings.general') }}">
                <i class="bi bi-sliders me-2"></i>General
              </a>
            </li>
            <li>
              <a class="nav-link ps-4 {{ request()->routeIs('settings.feed') ? 'active' : '' }}"
                 href="{{ route('settings.feed') }}">
                <i class="bi bi-rss me-2"></i>Feed
              </a>
            </li>
            <li>
              <a class="nav-link ps-4 {{ request()->routeIs('settings.plugins') ? 'active' : '' }}"
                 href="{{ route('settings.plugins') }}">
                <i class="bi bi-plug me-2"></i>Plugins
              </a>
            </li>
            <li>
              <a class="nav-link ps-4 {{ request()->routeIs('settings.import') ? 'active' : '' }}"
                 href="{{ route('settings.import') }}">
                <i class="bi bi-cloud-arrow-down me-2"></i>Import from RSS
              </a>
            </li>
          </ul>
        </div>
      </div>
    </nav>

    <div class="upgrade">
    
    </div>
  </aside>

  {{-- Mobile backdrop --}}
  <div id="sidebarBackdrop" class="sidebar-backdrop"></div>

  {{-- Topbar --}}
  <header class="topbar d-flex align-items-center justify-content-between px-3 px-lg-4 py-2">
    <div class="d-lg-none">
      <button class="btn btn-outline-secondary" id="openSidebar" type="button">
        <i class="bi bi-list"></i>
      </button>
    </div>

    <h1 class="h5 mb-0">@yield('page-title', 'Dashboard')</h1>

    <div class="d-flex align-items-center gap-2">
      <a class="btn btn-blush" data-bs-toggle="modal" data-bs-target="#episodeModal">
        <i class="bi bi-plus-lg me-1"></i>New Episode
      </a>

      <button type="button" class="btn btn-outline-secondary d-none d-sm-inline-flex">
        <i class="bi bi-life-preserver me-1"></i>Support
      </button>

      @php
        $user = auth()->user();
        $avatar = $user?->avatar_url ?? null;
      @endphp
      <div class="dropdown">
        <button class="btn btn-outline-secondary d-inline-flex align-items-center gap-2 dropdown-toggle"
                data-bs-toggle="dropdown" type="button">
          @if($avatar)
            <img src="{{ $avatar }}" alt="Profile" class="rounded-circle object-fit-cover" style="width:22px;height:22px;">
          @else
            <i class="bi bi-person-circle"></i>
          @endif
          <span class="d-none d-sm-inline">{{ $user?->name ?? 'User' }}</span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
          <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="bi bi-person me-2"></i>Profile</a></li>
          <li><a class="dropdown-item" href="{{ route('settings.index') }}"><i class="bi bi-gear me-2"></i>Settings</a></li>
          <li><hr class="dropdown-divider"></li>
          <li>
            <form method="POST" action="{{ route('logout') }}">
              @csrf
              <button class="dropdown-item text-danger" type="submit">
                <i class="bi bi-box-arrow-right me-2"></i>Logout
              </button>
            </form>
          </li>
        </ul>
      </div>
    </div>
  </header>

  {{-- Main content --}}
  <main class="main p-3 p-lg-4">
    @yield('content')
  </main>
</div>

{{-- Shared "New Episode" modal --}}
@include('episodes._modal_create')

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
  const sidebar  = document.getElementById('sidebar');
  const backdrop = document.getElementById('sidebarBackdrop');
  const openBtn  = document.getElementById('openSidebar');

  if (openBtn) openBtn.addEventListener('click', () => {
    sidebar.classList.add('show'); backdrop.classList.add('show');
  });
  if (backdrop) backdrop.addEventListener('click', () => {
    sidebar.classList.remove('show'); backdrop.classList.remove('show');
  });

  // Avoid anchors with "#" from polluting the URL
  document.querySelectorAll('a[href="#"]').forEach(a => a.addEventListener('click', e => e.preventDefault()));

  // Auto re-open "New Episode" modal after validation errors
  const shouldOpen = @json(old('_show_episode_modal') ? true : false);
  if (shouldOpen) new bootstrap.Modal(document.getElementById('episodeModal')).show();
})();
</script>

@stack('modals')
@stack('scripts')
</body>
</html>