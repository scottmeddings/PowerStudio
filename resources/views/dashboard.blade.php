{{-- resources/views/dashboard.blade.php --}}
<!doctype html>
<html lang="{{ str_replace('_','-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Powerpod · Dashboard</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

  <style>
    :root{
      --brand-1:#6366f1; /* indigo */
      --brand-2:#06b6d4; /* cyan   */
      --sidebar-w: 260px;
    }
    body { background-color:#f5f7fb; }

    /* Layout */
    .app {
      min-height: 100vh;
      display: grid;
      grid-template-columns: var(--sidebar-w) 1fr;
      grid-template-rows: auto 1fr;
      grid-template-areas:
        "sidebar topbar"
        "sidebar main";
    }
    .sidebar { grid-area: sidebar; }
    .topbar  { grid-area: topbar; }
    .main    { grid-area: main; }

    /* Sidebar */
    .sidebar{
      background: #0f172a; /* slate-900-ish */
      color:#cbd5e1;
      position: sticky; top:0; height:100vh; padding: 1rem 0;
    }
    .sidebar .brand{
      display:flex; align-items:center; gap:.75rem; padding:.5rem 1.25rem 1rem 1.25rem;
      color:#fff; text-decoration:none;
    }
    .brand-badge{
      width:34px;height:34px;border-radius:10px;
      display:inline-grid;place-items:center;color:#fff;
      background: linear-gradient(135deg,var(--brand-1),var(--brand-2));
      box-shadow:0 10px 20px rgba(99,102,241,.35);
    }
    .sidebar .nav-link{
      color:#cbd5e1; border-radius:.5rem; padding:.6rem 1rem; margin:.2rem .75rem;
    }
    .sidebar .nav-link.active,
    .sidebar .nav-link:hover{
      color:#fff; background: rgba(255,255,255,.08);
    }
    .sidebar .upgrade{
      position:absolute; left:1rem; right:1rem; bottom:1rem;
    }

    /* Topbar */
    .topbar{
      background: #fff; border-bottom:1px solid rgba(0,0,0,.06);
      position: sticky; top:0; z-index: 10;
    }

    /* Page sections */
    .section-card{
      background:#fff; border:1px solid rgba(0,0,0,.06);
      border-radius: .75rem; box-shadow: 0 10px 30px rgba(0,0,0,.03);
    }

    /* Stat tiles */
    .tile{
      border:1px solid rgba(0,0,0,.06); border-radius:.75rem; background:#fff;
      padding: 1rem 1.25rem;
    }
    .tile h3 { font-size: .9rem; color:#64748b; margin:0 0 .3rem; }
    .tile .value{ font-weight:700; font-size: 1.75rem; letter-spacing:.02em; }
    .sparkline { display:block; width:100%; height:28px; }

    /* Trend chart (pure SVG) */
    .area-chart { width:100%; height:280px; display:block; }
    .chart-wrap { padding:1rem; }

    /* Right rail cards */
    .badge-icon{
      width:36px;height:36px;border-radius:999px; display:inline-grid;place-items:center;
      background:linear-gradient(135deg,var(--brand-1),var(--brand-2)); color:#fff;
    }

    /* Episode table */
    .table > :not(caption) > * > * { padding-top:.85rem; padding-bottom:.85rem; }

    /* Hero banner (optional image) */
    .hero {
      position:relative; border-radius:.75rem; overflow:hidden;
      background:
        linear-gradient(180deg, rgba(255,255,255,.82), rgba(255,255,255,.82)),
        url("{{ asset('images/powerpod-podcast-bg.png') }}");
      background-size: cover; background-position:center;
      border:1px solid rgba(0,0,0,.06);
    }
    .hero .inner{ padding:1rem 1.25rem; }
    .hero h1 { font-size:1.25rem; margin:0; }

    /* Responsive */
    @media (max-width: 992px){
      .app{ grid-template-columns: 1fr; grid-template-areas:
              "topbar" "main"; }
      .sidebar{ position: fixed; inset:0 auto 0 0; width: var(--sidebar-w); transform: translateX(-100%); transition: transform .25s; z-index: 1050;}
      .sidebar.show{ transform: translateX(0); }
      .sidebar-backdrop{ display:none; position:fixed; inset:0; background:rgba(0,0,0,.35); z-index:1049;}
      .sidebar-backdrop.show{ display:block; }
    }
  </style>
</head>
<body>

<div class="app">
  {{-- SIDEBAR --}}
  <aside id="sidebar" class="sidebar">
    <a href="{{ url('/') }}" class="brand">
      <span class="brand-badge">
        <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true">
          <rect x="9" y="3" width="6" height="10" rx="3" ry="3" fill="currentColor"/>
          <path d="M5 11a7 7 0 0 0 14 0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
          <path d="M12 17v4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
          <path d="M8 21h8" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>
      </span>
      <span class="fw-semibold">Powerpod</span>
    </a>

    <nav class="mt-2">
      <a class="nav-link active" href="#"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>
      <a class="nav-link" href="#"><i class="bi bi-mic me-2"></i>Episodes</a>
      <a class="nav-link" href="#"><i class="bi bi-broadcast-pin me-2"></i>Distribution</a>
      <a class="nav-link" href="#"><i class="bi bi-graph-up-arrow me-2"></i>Statistics</a>
      <a class="nav-link" href="#"><i class="bi bi-currency-dollar me-2"></i>Monetization</a>
      <a class="nav-link" href="#"><i class="bi bi-gear me-2"></i>Settings</a>
    </nav>

    <div class="upgrade">
      <a class="btn btn-outline-light w-100" href="#"><i class="bi bi-stars me-1"></i>Upgrade</a>
    </div>
  </aside>

  {{-- mobile sidebar backdrop --}}
  <div id="sidebarBackdrop" class="sidebar-backdrop"></div>

  {{-- TOPBAR --}}
  <header class="topbar d-flex align-items-center justify-content-between px-3 px-lg-4 py-2">
    <div class="d-lg-none">
      <button class="btn btn-outline-secondary" id="openSidebar"><i class="bi bi-list"></i></button>
    </div>
    <div>
      <div class="hero d-none d-md-block">
        <div class="inner">
          <h1 class="mb-0">Dashboard</h1>
          <small class="text-secondary">Here’s what’s happening with your podcast.</small>
        </div>
      </div>
      <div class="d-md-none">
        <h1 class="h5 mb-0">Dashboard</h1>
      </div>
    </div>

    <div class="d-flex align-items-center gap-2">
      <a class="btn btn-dark" href="#"><i class="bi bi-plus-lg me-1"></i>New Episode</a>
      <a class="btn btn-outline-secondary d-none d-sm-inline-flex" href="#"><i class="bi bi-life-preserver me-1"></i>Support</a>

      <div class="dropdown">
        <button class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
          <i class="bi bi-person-circle me-1"></i>{{ auth()->user()->name ?? 'User' }}
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
          <li><a class="dropdown-item" href="#"><i class="bi bi-person me-2"></i>Profile</a></li>
          <li><a class="dropdown-item" href="#"><i class="bi bi-gear me-2"></i>Settings</a></li>
          <li><hr class="dropdown-divider"></li>
          <li>
            <form method="POST" action="{{ route('logout') }}">
              @csrf
              <button class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i>Logout</button>
            </form>
          </li>
        </ul>
      </div>
    </div>
  </header>

  {{-- MAIN CONTENT --}}
  <main class="main p-3 p-lg-4">

    {{-- Stat tiles --}}
    <div class="row g-3">
      <div class="col-12 col-sm-6 col-lg-3">
        <div class="tile">
          <h3>Yesterday Downloads</h3>
          <div class="d-flex align-items-end justify-content-between">
            <div class="value">{{ $metrics['yesterday'] ?? 1 }}</div>
            <svg class="sparkline" viewBox="0 0 100 28" preserveAspectRatio="none">
              <polyline fill="none" stroke="#22c55e" stroke-width="2"
                        points="2,18 15,12 28,16 41,24 54,10 67,12 80,8 93,20"/>
            </svg>
          </div>
        </div>
      </div>

      <div class="col-12 col-sm-6 col-lg-3">
        <div class="tile">
          <h3>Last 7 Days Downloads</h3>
          <div class="d-flex align-items-end justify-content-between">
            <div class="value">{{ $metrics['last7'] ?? 25 }}</div>
            <svg class="sparkline" viewBox="0 0 100 28">
              <polyline fill="none" stroke="#16a34a" stroke-width="2"
                        points="2,20 15,18 28,14 41,16 54,12 67,8 80,12 93,10"/>
            </svg>
          </div>
        </div>
      </div>

      <div class="col-12 col-sm-6 col-lg-3">
        <div class="tile">
          <h3>Last 30 Days Downloads</h3>
          <div class="d-flex align-items-end justify-content-between">
            <div class="value">{{ $metrics['last30'] ?? 162 }}</div>
            <svg class="sparkline" viewBox="0 0 100 28">
              <polyline fill="none" stroke="#06b6d4" stroke-width="2"
                        points="2,22 15,16 28,14 41,10 54,16 67,14 80,12 93,16"/>
            </svg>
          </div>
        </div>
      </div>

      <div class="col-12 col-sm-6 col-lg-3">
        <div class="tile">
          <h3>All time Downloads</h3>
          <div class="d-flex align-items-end justify-content-between">
            <div class="value">{{ $metrics['allTime'] ?? '2.8K' }}</div>
            <svg class="sparkline" viewBox="0 0 100 28">
              <polyline fill="none" stroke="#f59e0b" stroke-width="2"
                        points="2,22 15,20 28,18 41,16 54,14 67,12 80,10 93,12"/>
            </svg>
          </div>
        </div>
      </div>
    </div>

    <div class="row g-3 mt-1">
      {{-- Downloads Trending --}}
      <div class="col-lg-8">
        <div class="section-card p-3">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <div>
              <h5 class="mb-0">Downloads Trending</h5>
              <small class="text-danger fw-semibold">47 <span class="text-muted fw-normal ms-1">vs previous 14 days</span></small>
            </div>
            <a href="#" class="btn btn-outline-secondary btn-sm">View More</a>
          </div>

          <div class="chart-wrap">
            <!-- Simple area chart -->
            <svg class="area-chart" viewBox="0 0 640 280" preserveAspectRatio="none">
              <!-- grid lines -->
              <g stroke="rgba(0,0,0,.06)" stroke-width="1">
                <line x1="0" y1="230" x2="640" y2="230"/>
                <line x1="0" y1="180" x2="640" y2="180"/>
                <line x1="0" y1="130" x2="640" y2="130"/>
                <line x1="0" y1="80"  x2="640" y2="80"/>
              </g>
              <!-- area -->
              <defs>
                <linearGradient id="areaFill" x1="0" y1="0" x2="0" y2="1">
                  <stop offset="0%" stop-color="#22c55e" stop-opacity=".35"/>
                  <stop offset="100%" stop-color="#22c55e" stop-opacity="0"/>
                </linearGradient>
              </defs>
              <path d="M0,200
                       C 60,160 120,220 180,140
                       S 300,120 360,200
                       S 480,240 540,150
                       S 640,210 640,210
                       L 640,280 L 0,280 Z"
                    fill="url(#areaFill)" />
              <!-- line -->
              <path d="M0,200
                       C 60,160 120,220 180,140
                       S 300,120 360,200
                       S 480,240 540,150
                       S 640,210 640,210"
                    fill="none" stroke="#22c55e" stroke-width="3"/>
              <!-- points -->
              <g fill="#22c55e">
                <circle cx="180" cy="140" r="3"/>
                <circle cx="360" cy="200" r="3"/>
                <circle cx="540" cy="150" r="3"/>
                <circle cx="640" cy="210" r="3"/>
              </g>
            </svg>
          </div>
        </div>

        {{-- Episode Performance --}}
        <div class="section-card p-3 mt-3">
          <div class="d-flex align-items-center justify-content-between">
            <h5 class="mb-0">Episode Performance</h5>
            <a href="#" class="btn btn-outline-secondary btn-sm">View More</a>
          </div>
          <div class="table-responsive mt-2">
            <table class="table align-middle">
              <thead class="table-light">
              <tr>
                <th>Episode Title</th>
                <th class="text-end">First Week</th>
                <th class="text-end">First Month</th>
              </tr>
              </thead>
              <tbody>
              <tr>
                <td>E19: CVP Power Platform Ryan Cunningham chat to Matt and Scott about the Platform and AI</td>
                <td class="text-end">77</td>
                <td class="text-end">100</td>
              </tr>
              <tr>
                <td>Special Edition - Kids Helpline’s Tracy Adams on the 24/7 Campaign and Why the Contact Centre Is Crucial</td>
                <td class="text-end">59</td>
                <td class="text-end">79</td>
              </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      {{-- Right rail --}}
      <div class="col-lg-4">
        <div class="section-card p-3">
          <h5 class="mb-3">Achievements</h5>
          <div class="d-flex align-items-start gap-3 mb-3">
            <span class="badge-icon"><i class="bi bi-trophy"></i></span>
            <div>
              <div class="fw-semibold">Congratulations on <strong>2,000</strong> Downloads!</div>
              <small class="text-secondary">Nice milestone.</small>
            </div>
          </div>
          <div class="d-flex align-items-start gap-3">
            <span class="badge-icon"><i class="bi bi-award"></i></span>
            <div>
              <div class="fw-semibold">Congratulations on publishing <strong>10 episodes!</strong></div>
              <small class="text-secondary">Keep the momentum.</small>
            </div>
          </div>
          <a href="#" class="btn btn-outline-secondary w-100 mt-3">View Badges</a>
        </div>

        <div class="section-card p-4 mt-3 text-center">
          <h5 class="mb-3">Recent Comments</h5>
          <div class="text-secondary">
            <i class="bi bi-chat-square-text fs-1 d-block mb-2"></i>
            No comments yet.
          </div>
        </div>
      </div>
    </div>
  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Mobile sidebar toggle
  const sidebar = document.getElementById('sidebar');
  const backdrop = document.getElementById('sidebarBackdrop');
  const openBtn  = document.getElementById('openSidebar');

  if (openBtn) {
    openBtn.addEventListener('click', () => {
      sidebar.classList.add('show');
      backdrop.classList.add('show');
    });
    backdrop.addEventListener('click', () => {
      sidebar.classList.remove('show');
      backdrop.classList.remove('show');
    });
  }
</script>
</body>
</html>
