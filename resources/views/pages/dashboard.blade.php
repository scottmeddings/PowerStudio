@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'dashboard')

@section('content')
  {{-- Stat tiles --}}
  <div class="row g-3">
    <div class="col-12 col-sm-6 col-lg-3">
      <div class="tile">
        <h3>Yesterday Downloads</h3>
        <div class="d-flex align-items-end justify-content-between">
          <div class="value">{{ $metrics['yesterday'] ?? 0 }}</div>
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
          <div class="value">{{ $metrics['last7'] ?? 0 }}</div>
          <svg class="sparkline" viewBox="0 0 100 28" preserveAspectRatio="none">
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
          <div class="value">{{ $metrics['last30'] ?? 0 }}</div>
          <svg class="sparkline" viewBox="0 0 100 28" preserveAspectRatio="none">
            <polyline fill="none" stroke="#06b6d4" stroke-width="2"
              points="2,22 15,16 28,14 41,10 54,16 67,14 80,12 93,16"/>
          </svg>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-lg-3">
      <div class="tile">
        <h3>All Time Downloads</h3>
        <div class="d-flex align-items-end justify-content-between">
          <div class="value">{{ $metrics['allTime'] ?? 0 }}</div>
          <svg class="sparkline" viewBox="0 0 100 28" preserveAspectRatio="none">
            <polyline fill="none" stroke="#f59e0b" stroke-width="2"
              points="2,22 15,20 28,18 41,16 54,14 67,12 80,10 93,12"/>
          </svg>
        </div>
      </div>
    </div>
  </div>

  {{-- Main content --}}
  <div class="row g-3 mt-1">
    {{-- Left: Trending + Episode Performance --}}
    <div class="col-lg-8">
      <div class="section-card p-3">
        <div class="d-flex align-items-center justify-content-between mb-2">
          <div>
            <h5 class="mb-0">Downloads Trending</h5>
            <small class="text-danger fw-semibold">
              47 <span class="text-muted fw-normal ms-1">vs previous 14 days</span>
            </small>
          </div>
          <a href="{{ route('statistics') }}" class="btn btn-outline-secondary btn-sm">View More</a>
        </div>

        <div class="p-2">
          {{-- Simple area chart (static SVG placeholder) --}}
          <svg class="area-chart" viewBox="0 0 640 280" preserveAspectRatio="none" style="width:100%;height:280px;display:block">
            <g stroke="rgba(0,0,0,.06)" stroke-width="1">
              <line x1="0" y1="230" x2="640" y2="230"/>
              <line x1="0" y1="180" x2="640" y2="180"/>
              <line x1="0" y1="130" x2="640" y2="130"/>
              <line x1="0" y1="80"  x2="640" y2="80"/>
            </g>
            <defs>
              <linearGradient id="areaFill" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="#22c55e" stop-opacity=".35"/>
                <stop offset="100%" stop-color="#22c55e" stop-opacity="0"/>
              </linearGradient>
            </defs>
            <path d="M0,200 C 60,160 120,220 180,140 S 300,120 360,200 S 480,240 540,150 S 640,210 640,210 L 640,280 L 0,280 Z"
                  fill="url(#areaFill)" />
            <path d="M0,200 C 60,160 120,220 180,140 S 300,120 360,200 S 480,240 540,150 S 640,210 640,210"
                  fill="none" stroke="#22c55e" stroke-width="3"/>
            <g fill="#22c55e">
              <circle cx="180" cy="140" r="3"/>
              <circle cx="360" cy="200" r="3"/>
              <circle cx="540" cy="150" r="3"/>
              <circle cx="640" cy="210" r="3"/>
            </g>
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

      <div class="section-card p-4 mt-3 text-center">
        <h5 class="mb-3">Recent Comments</h5>
        <div class="text-secondary">
          <i class="bi bi-chat-square-text fs-1 d-block mb-2"></i>
          No comments yet.
        </div>
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
