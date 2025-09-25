<!doctype html>
<html lang="{{ str_replace('_','-', app()->getLocale()) }}" data-theme="light">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>PowerStudio · @yield('title', 'Dashboard')</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

  <style>
    :root{
      --brand-1:#6366f1; --brand-2:#06b6d4; --sidebar-w:260px;

      --c-bg: #f5f7fb; --c-text: #0b1220; --c-muted: #6b7280;
      --c-topbar-bg: #ffffff; --c-topbar-border: rgba(0,0,0,.06);
      --c-sidebar-bg: #0f172a; --c-sidebar-text: #cbd5e1; --c-sidebar-link: #cbd5e1;
      --c-sidebar-link-hover-bg: rgba(255,255,255,.08);
      --c-sidebar-link-active-bg: rgba(255,255,255,.08);
      --c-sidebar-collapse-active-bg: rgba(255,255,255,.06);
      --c-card-bg: #ffffff; --c-card-border: rgba(0,0,0,.06);
      --c-outline: #6c757d; --c-outline-hover-bg: #f1f3f5;
      --c-danger: #dc3545; --c-danger-contrast: #ffffff;
    }
    html[data-theme="dark"]{
      --c-bg: #0b1220; --c-text: #e5e7eb; --c-muted: #9aa3b2;
      --c-topbar-bg: #0f172a; --c-topbar-border: rgba(255,255,255,.08);
      --c-sidebar-bg: #0a101f; --c-sidebar-text: #b6c2d2; --c-sidebar-link: #b6c2d2;
      --c-sidebar-link-hover-bg: rgba(255,255,255,.06);
      --c-sidebar-link-active-bg: rgba(255,255,255,.10);
      --c-sidebar-collapse-active-bg: rgba(255,255,255,.08);
      --c-card-bg: #101829; --c-card-border: rgba(255,255,255,.08);
      --c-outline: #8b95a5; --c-outline-hover-bg: #1a2335;
      --c-danger:#ef4444; --c-danger-contrast:#ffffff;
    }

    body{ background-color:var(--c-bg); color:var(--c-text); }
    .app{
      min-height:100vh; display:grid;
      grid-template-columns:var(--sidebar-w) 1fr;
      grid-template-rows:auto 1fr;
      grid-template-areas:"sidebar topbar" "sidebar main";
    }
    .sidebar{ grid-area:sidebar; background:var(--c-sidebar-bg); color:var(--c-sidebar-text);
      position:sticky; top:0; height:100vh; padding:1rem 0; z-index:1030; }
    .topbar{ grid-area:topbar; background:var(--c-topbar-bg);
      border-bottom:1px solid var(--c-topbar-border); position:sticky; top:0; z-index:1010; }
    .main{ grid-area:main; position:relative; z-index:1; }

    .sidebar .brand-logo{ text-align:center; margin-bottom:1.5rem; }
    .sidebar .brand-logo img{ max-width:140px; height:auto; }

    .sidebar .nav-link{
      color:var(--c-sidebar-link); border-radius:.5rem;
      padding:.6rem 1rem; margin:.2rem .75rem;
      display:flex; align-items:center; justify-content:flex-start;
    }
    .sidebar .nav-link i.bi {
      display:inline-block; width:1.5rem; text-align:center;
      font-size:1rem; flex-shrink:0;
    }
    .sidebar .nav-link.active,
    .sidebar .nav-link:hover{ color:#fff; background:var(--c-sidebar-link-hover-bg); }
    .sidebar .collapse .nav-link.active{ background:var(--c-sidebar-collapse-active-bg); color:#fff; }

    .sidebar .dropdown-toggle-icon {
      display:inline-block; width:1.5rem; text-align:center;
    }

    @media (max-width:992px){
      .app{ grid-template-columns:1fr; grid-template-areas:"topbar" "main" }
      .sidebar{ position:fixed; inset:0 auto 0 0; width:var(--sidebar-w);
        transform:translateX(-100%); transition:transform .25s; z-index:1050 }
      .sidebar.show{ transform:translateX(0) }
      .sidebar-backdrop{ display:none; position:fixed; inset:0;
        background:rgba(0,0,0,.35); z-index:1049 }
      .sidebar-backdrop.show{ display:block }
    }
  </style>
  @stack('styles')
</head>
<body>
<div class="app">
  {{-- Sidebar --}}
  <aside id="sidebar" class="sidebar">
    <div class="brand-logo">
      <img src="{{ asset('images/powerstudio-logo.png') }}" alt="PowerStudio">
    </div>

    <nav class="mt-2">
      @php
        $role    = auth()->user()->role ?? 'user';
        $isAdmin = $role === 'admin';
      @endphp

      <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
        <i class="bi bi-speedometer2"></i>Dashboard
      </a>

      <a class="nav-link {{ request()->routeIs('episodes*') ? 'active' : '' }}" href="{{ route('episodes') }}">
        <i class="bi bi-mic"></i>Episodes
      </a>

      {{-- Distribution dropdown --}}
      @php
        $inDistribution = request()->routeIs('distribution*');
        $appsUrl    = \Route::has('distribution.apps') ? route('distribution.apps') : '#';
        $socialUrl  = \Route::has('distribution.social') ? route('distribution.social') : '#';
        $websiteUrl = \Route::has('distribution.website') ? route('distribution.website') : '#';
        $playerUrl  = \Route::has('distribution.player') ? route('distribution.player') : '#';
      @endphp
      <div class="mt-2">
        <div class="d-flex align-items-center justify-content-between mx-2">
          <a class="nav-link flex-grow-1 {{ $inDistribution ? 'active' : '' }}" href="#">
            <i class="bi bi-broadcast-pin"></i>Distribution
          </a>
          <button class="btn btn-sm btn-outline-secondary ms-2" type="button"
                  data-bs-toggle="collapse" data-bs-target="#distributionMenu"
                  aria-controls="distributionMenu" aria-expanded="{{ $inDistribution ? 'true' : 'false' }}">
            <span class="dropdown-toggle-icon"><i class="bi bi-chevron-down"></i></span>
          </button>
        </div>
        <div id="distributionMenu" class="collapse {{ $inDistribution ? 'show' : '' }}">
          <ul class="list-unstyled my-2">
            <li><a class="nav-link ps-4 {{ request()->routeIs('distribution.apps') ? 'active' : '' }}" href="{{ $appsUrl }}"><i class="bi bi-app-indicator"></i>Podcast Apps</a></li>
            <li><a class="nav-link ps-4 {{ request()->routeIs('distribution.social') ? 'active' : '' }}" href="{{ $socialUrl }}"><i class="bi bi-share"></i>Social Share</a></li>
            <li><a class="nav-link ps-4 {{ request()->routeIs('distribution.website') ? 'active' : '' }}" href="{{ $websiteUrl }}"><i class="bi bi-globe2"></i>Podcast Website</a></li>
            <li><a class="nav-link ps-4 {{ request()->routeIs('distribution.player') ? 'active' : '' }}" href="{{ $playerUrl }}"><i class="bi bi-play-btn"></i>Embeddable Player</a></li>
          </ul>
        </div>
      </div>

      <a class="nav-link {{ request()->routeIs('statistics*') ? 'active' : '' }}" href="{{ route('statistics') }}">
        <i class="bi bi-graph-up-arrow"></i>Statistics
      </a>

      <a class="nav-link {{ request()->routeIs('monetization*') ? 'active' : '' }}" href="{{ route('monetization') }}">
        <i class="bi bi-currency-dollar"></i>Monetization
      </a>

      {{-- Settings dropdown --}}
      @php $inSettings = request()->routeIs('settings.*'); @endphp
      <div class="mt-2">
        <div class="d-flex align-items-center justify-content-between mx-2">
          <a class="nav-link flex-grow-1 {{ $inSettings ? 'active' : '' }}" href="{{ route('settings.index') }}">
            <i class="bi bi-gear"></i>Settings
          </a>
          <button class="btn btn-sm btn-outline-secondary ms-2" type="button"
                  data-bs-toggle="collapse" data-bs-target="#settingsMenu"
                  aria-controls="settingsMenu" aria-expanded="{{ $inSettings ? 'true' : 'false' }}">
            <span class="dropdown-toggle-icon"><i class="bi bi-chevron-down"></i></span>
          </button>
        </div>
        <div id="settingsMenu" class="collapse {{ $inSettings ? 'show' : 'hide' }}">
          <ul class="list-unstyled my-2">
            <li><a class="nav-link ps-4 {{ request()->routeIs('settings.general') ? 'active' : '' }}" href="{{ route('settings.general') }}"><i class="bi bi-sliders"></i>General</a></li>
            <li><a class="nav-link ps-4 {{ request()->routeIs('settings.feed') ? 'active' : '' }}" href="{{ route('settings.feed') }}"><i class="bi bi-rss"></i>Feed</a></li>
            <li><a class="nav-link ps-4 {{ request()->routeIs('settings.plugins') ? 'active' : '' }}" href="{{ route('settings.plugins') }}"><i class="bi bi-plug"></i>Plugins</a></li>
            <li><a class="nav-link ps-4 {{ request()->routeIs('settings.import') ? 'active' : '' }}" href="{{ route('settings.import') }}"><i class="bi bi-cloud-arrow-down"></i>Import from RSS</a></li>
          </ul>
        </div>
      </div>

      {{-- Administration dropdown (admins only) --}}
      @php
        $inAdmin   = request()->routeIs('admin.*') || request()->routeIs('test.totals');
        $usersUrl  = \Route::has('admin.users.index') ? route('admin.users.index') : url('/admin/users');
        $database  = \Route::has('test.totals') ? route('test.totals') : url('/test/totals');
      @endphp
      @if($isAdmin)
        <div class="mt-2">
          <div class="d-flex align-items-center justify-content-between mx-2">
            <a class="nav-link flex-grow-1 {{ $inAdmin ? 'active' : '' }}" href="{{ $usersUrl }}">
              <i class="bi bi-shield-lock"></i>Administration
            </a>
            <button class="btn btn-sm btn-outline-secondary ms-2" type="button"
                    data-bs-toggle="collapse" data-bs-target="#adminMenu"
                    aria-controls="adminMenu" aria-expanded="{{ $inAdmin ? 'true' : 'false' }}">
              <span class="dropdown-toggle-icon"><i class="bi bi-chevron-down"></i></span>
            </button>
          </div>
          <div id="adminMenu" class="collapse {{ $inAdmin ? 'show' : '' }}">
            <ul class="list-unstyled my-2">
              <li><a class="nav-link ps-4 {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ $usersUrl }}"><i class="bi bi-person-lines-fill"></i>User Management</a></li>
              <li><a class="nav-link ps-4 {{ request()->routeIs('test.totals') ? 'active' : '' }}" href="{{ $database }}"><i class="bi bi-database-gear"></i>Database</a></li>
            </ul>
          </div>
        </div>
      @endif
    </nav>
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
      {{-- THEME TOGGLE --}}
      <button id="themeToggle" type="button" class="btn btn-outline-secondary" aria-pressed="false" title="Toggle dark mode">
        <i class="bi bi-moon-stars" id="iconMoon"></i>
        <i class="bi bi-sun d-none" id="iconSun"></i>
      </button>

      <a class="btn btn-blush" data-bs-toggle="modal" data-bs-target="#episodeModal">
        <i class="bi bi-plus-lg me-1"></i>New Episode
      </a>

      <button type="button" class="btn btn-outline-secondary d-none d-sm-inline-flex">
        <i class="bi bi-life-preserver me-1"></i>Support
      </button>

      @php $user = auth()->user(); $avatar = $user?->avatar_url ?? null; @endphp
      <div class="dropdown">
        <button class="btn btn-outline-secondary d-inline-flex align-items-center gap-2 dropdown-toggle" data-bs-toggle="dropdown" type="button">
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
              <meta name="csrf-token" content="{{ csrf_token() }}">
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

  document.querySelectorAll('a[href="#"]').forEach(a => a.addEventListener('click', e => e.preventDefault()));

  const shouldOpen = @json(old('_show_episode_modal') ? true : false);
  if (shouldOpen) new bootstrap.Modal(document.getElementById('episodeModal')).show();

  const html = document.documentElement;
  const toggleBtn = document.getElementById('themeToggle');
  const iconMoon  = document.getElementById('iconMoon');
  const iconSun   = document.getElementById('iconSun');

  function applyTheme(theme){
    html.setAttribute('data-theme', theme);
    const dark = (theme === 'dark');
    toggleBtn.setAttribute('aria-pressed', String(dark));
    if (dark){ iconMoon.classList.add('d-none'); iconSun.classList.remove('d-none'); }
    else     { iconMoon.classList.remove('d-none'); iconSun.classList.add('d-none'); }
  }

  const saved = localStorage.getItem('theme');
  const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
  applyTheme(saved ?? (prefersDark ? 'dark' : 'light'));

  toggleBtn?.addEventListener('click', () => {
    const next = (html.getAttribute('data-theme') === 'dark') ? 'light' : 'dark';
    applyTheme(next);
    localStorage.setItem('theme', next);
  });

  try{
    const mq = window.matchMedia('(prefers-color-scheme: dark)');
    mq.addEventListener?.('change', e => {
      if (!localStorage.getItem('theme')) {
        applyTheme(e.matches ? 'dark' : 'light');
      }
    });
  } catch(_) {}
})();
</script>

@stack('modals')
@stack('scripts')
</body>
</html>
