@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'dashboard')

@section('content')
  {{-- Stat tiles --}}
<div class="row g-3">
  @foreach ([
    $tiles['yesterday'],
    $tiles['last7'],
    $tiles['last30'],
    $tiles['all'],
  ] as $t)
    <div class="col-12 col-sm-6 col-lg-3">
      <div class="tile">
        <h3>{{ $t['label'] }}</h3>
        <div class="d-flex align-items-end justify-content-between">
          <div class="value">{{ number_format($t['value']) }}</div>

          @php
            $s   = $t['series'] ?? [];
            $W   = 180; $H = 64;             // sparkline size
            $pad = 6;  $base = $H - 8;       // bottom line
            $usable = $base - 10;            // vertical space

            $n    = count($s);
            $max  = max(1, max($s ?: [0]));
            $step = $n > 1 ? ($W - 2*$pad) / ($n - 1) : 0;

            $pts = '';
            for ($i = 0; $i < $n; $i++) {
              $x = $pad + $i * $step;
              $y = $base - ($s[$i] / $max) * $usable;
              $pts .= round($x, 1) . ',' . round($y, 1) . ' ';
            }
          @endphp

          <svg viewBox="0 0 {{ $W }} {{ $H }}" width="{{ $W }}" height="{{ $H }}"
               class="sparkline" preserveAspectRatio="none">
            <polyline fill="none" stroke="{{ $t['color'] }}" stroke-width="3" points="{{ $pts }}" />
          </svg>
        </div>
      </div>
    </div>
  @endforeach
</div>


  {{-- Main content --}}
  <div class="row g-3 mt-1">
    {{-- Left: Trending + Episode Performance --}}
    <div class="col-lg-8">
    <div class="section-card p-3">
  <div class="d-flex align-items-center justify-content-between mb-2">
    <div>
      <h5 class="mb-0">Downloads Trending</h5>
      @php
        $delta = $trending['delta'] ?? 0;
        $deltaClass = $delta >= 0 ? 'text-success' : 'text-danger';
        $deltaPrefix = $delta > 0 ? '+' : '';
      @endphp
      <small class="{{ $deltaClass }} fw-semibold">
        {{ $deltaPrefix }}{{ number_format($delta) }}
        <span class="text-muted fw-normal ms-1">vs previous {{ $trending['days'] }} days</span>
      </small>
    </div>
    <a href="{{ route('statistics') }}" class="btn btn-outline-secondary btn-sm">View More</a>
  </div>

  @php
    $series = $trending['series'] ?? [];
    $n      = count($series);

    // Chart sizing
    $W = 760;           // width
    $H = 260;           // height
    $padX = 12;         // horizontal padding
    $padTop = 14;       // top gap
    $baseline = $H - 18;

    $stepX = $n > 1 ? ($W - 2 * $padX) / ($n - 1) : 0;
    $max   = max(1, $trending['max'] ?? 1);
    $usableHeight = ($baseline - $padTop);

    $points = '';
    $areaPoints = $padX . ',' . $baseline . ' '; // start on baseline

    for ($i = 0; $i < $n; $i++) {
        $x = $padX + $i * $stepX;
        $val = $series[$i]['count'];
        $y = $baseline - ($val / $max) * $usableHeight;

        $points    .= round($x, 1) . ',' . round($y, 1) . ' ';
        $areaPoints .= round($x, 1) . ',' . round($y, 1) . ' ';
    }
    // close the area down to the baseline
    $areaPoints .= ($padX + ($n > 0 ? ($n - 1) * $stepX : 0)) . ',' . $baseline . ' ' . $padX . ',' . $baseline;
  @endphp

  <div class="p-2">
    <svg viewBox="0 0 {{ $W }} {{ $H }}" width="100%" height="280" preserveAspectRatio="none" style="display:block">
      {{-- grid lines --}}
      <g stroke="rgba(0,0,0,.06)" stroke-width="1">
        <line x1="0" y1="{{ $baseline }}" x2="{{ $W }}" y2="{{ $baseline }}"/>
        <line x1="0" y1="{{ $baseline - $usableHeight * .33 }}" x2="{{ $W }}" y2="{{ $baseline - $usableHeight * .33 }}"/>
        <line x1="0" y1="{{ $baseline - $usableHeight * .66 }}" x2="{{ $W }}" y2="{{ $baseline - $usableHeight * .66 }}"/>
      </g>

      <defs>
        <linearGradient id="dlFill" x1="0" y1="0" x2="0" y2="1">
          <stop offset="0%"  stop-color="#22c55e" stop-opacity=".35"/>
          <stop offset="100%" stop-color="#22c55e" stop-opacity="0"/>
        </linearGradient>
      </defs>

      {{-- filled area --}}
      @if($n > 0)
        <polygon points="{{ $areaPoints }}" fill="url(#dlFill)"></polygon>
        {{-- trend line --}}
        <polyline fill="none" stroke="#22c55e" stroke-width="3" points="{{ $points }}"></polyline>
        {{-- markers (first/mid/last) --}}
        @php
          $markerIdx = array_unique([0, intval(($n-1)/2), max(0,$n-1)]);
        @endphp
        @foreach($markerIdx as $idx)
          @php
            $mx = $padX + $idx * $stepX;
            $my = $baseline - (($series[$idx]['count'] ?? 0) / $max) * $usableHeight;
          @endphp
          <circle cx="{{ $mx }}" cy="{{ $my }}" r="4" fill="#22c55e"></circle>
        @endforeach
      @endif
    </svg>
  </div>
</div>


      <div class="section-card p-3 mt-3">
        <div class="d-flex align-items-center justify-content-between">
          <h5 class="mb-0">Episode Performance</h5>
          <a href="{{ route('episodes') }}" class="btn btn-outline-secondary btn-sm">View More</a>
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
              @forelse($episodesPerformance as $ep)
                <tr>
                  <td>{{ $ep->title }}</td>
                  <td class="text-end">{{ number_format($ep->first_week) }}</td>
                  <td class="text-end">{{ number_format($ep->first_month) }}</td>
                </tr>
              @empty
                <tr>
                  <td colspan="3" class="text-center text-secondary">No data yet.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    {{-- Right: Achievements + Comments --}}
    <div class="col-lg-4">
      @php
        $unlocked = collect($achUnlocked ?? []);
        $locked   = collect($achLocked ?? []);
        $show     = $unlocked->take(2);
        if ($show->isEmpty()) { $show = $locked->take(2); }
      @endphp

      <div class="section-card p-3">
        <h5 class="mb-3">Achievements</h5>

        @forelse($show as $a)
          <div class="d-flex align-items-start gap-3 mb-3">
            <span class="badge-icon"
                  style="width:36px;height:36px;border-radius:999px;display:inline-grid;place-items:center;background:linear-gradient(135deg,#6366f1,#06b6d4);color:#fff;">
              <i class="bi {{ $a['icon'] }}"></i>
            </span>
            <div>
              <div class="fw-semibold">
                {{ $a['title'] }}
                @if($a['unlocked'])
                  <span class="text-success ms-1">✓</span>
                @endif
              </div>
              <small class="text-secondary">
                {{ $a['desc'] }}
                @unless($a['unlocked'])
                  · {{ number_format($a['remaining']) }} to go
                @endunless
              </small>
            </div>
          </div>
        @empty
          <div class="text-secondary">No achievements yet.</div>
        @endforelse

        <button class="btn btn-outline-secondary w-100 mt-2"
                data-bs-toggle="modal" data-bs-target="#achievementsModal">
          View Badges
        </button>
      </div>

      <div class="section-card p-3 mt-3">
        <h5 class="mb-3">Recent Comments</h5>

        @forelse($recentComments as $c)
          <div class="mb-3 pb-3 border-bottom">
            <div class="d-flex justify-content-between">
              <div class="fw-semibold">{{ $c->user->name ?? 'Guest' }}</div>
              <small class="text-secondary">{{ $c->created_at->diffForHumans() }}</small>
            </div>
            <div class="text-secondary small">
              on <a href="{{ route('episodes.show', $c->episode) }}">{{ \Illuminate\Support\Str::limit($c->episode->title, 48) }}</a>
            </div>
            <div>{{ \Illuminate\Support\Str::limit($c->body, 160) }}</div>
          </div>
        @empty
          <div class="text-secondary text-center">
            <i class="bi bi-chat-square-text fs-1 d-block mb-2"></i>
            No comments yet.
          </div>
        @endforelse
      </div>

    </div>
  </div>
@endsection

{{-- Modal lives in the global @stack so it’s rendered at the end of <body> --}}
@push('modals')
<div class="modal fade" id="achievementsModal" tabindex="-1" aria-labelledby="achievementsTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 id="achievementsTitle" class="modal-title">All Achievements</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        @foreach(collect($allAchievements ?? []) as $a)
          <div class="d-flex align-items-start justify-content-between py-2 border-bottom">
            <div class="d-flex align-items-start gap-3">
              <span class="badge-icon"
                    style="width:36px;height:36px;border-radius:999px;display:inline-grid;place-items:center;background:linear-gradient(135deg,#6366f1,#06b6d4);color:#fff;">
                <i class="bi {{ $a['icon'] }}"></i>
              </span>
              <div>
                <div class="fw-semibold">
                  {{ $a['title'] }}
                  @if($a['unlocked'])
                    <span class="badge text-bg-success ms-2">Unlocked</span>
                  @else
                    <span class="badge text-bg-secondary ms-2">Locked</span>
                  @endif
                </div>
                <small class="text-secondary">{{ $a['desc'] }}</small>
              </div>
            </div>
            <div class="text-end" style="min-width:140px">
              <small class="text-secondary d-block mb-1">
                {{ number_format($a['current']) }} / {{ number_format($a['threshold']) }}
              </small>
              <div class="progress" style="height:6px;">
                <div class="progress-bar {{ $a['unlocked'] ? 'bg-success' : '' }}"
                     style="width: {{ $a['progress'] }}%"></div>
              </div>
            </div>
          </div>
        @endforeach
      </div>

      <div class="modal-footer">
        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
@endpush
