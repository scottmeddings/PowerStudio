@extends('layouts.app')

@section('title', 'Statistics')
@section('page-title', 'statistics')

@section('content')
@php
  $max = max(1, collect($series)->max('count')); // avoid div-by-zero
  $n   = count($series);
  $W   = 640;   // chart width
  $H   = 240;   // chart height
  $pad = 10;    // inner padding
  $usableW = $W - ($pad * 2);
  $usableH = $H - ($pad * 2);

  // Generate points for the polyline
  $points = [];
  foreach ($series as $i => $row) {
      $x = $n > 1 ? $pad + ($usableW * ($i / ($n - 1))) : ($W / 2);
      $y = $pad + $usableH - ($row['count'] / $max) * $usableH;
      $points[] = round($x, 1) . ',' . round($y, 1);
  }
  // Simple polygon for area fill: bottom-left -> line -> bottom-right
  $polyFill = "{$pad},".($pad+$usableH)." ".implode(' ', $points)." ".($pad+$usableW).",".($pad+$usableH);
@endphp

{{-- Filter / header --}}
<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="mb-0">Overview</h5>
  <div class="btn-group" role="group" aria-label="Date range">
    @foreach([7,30,90] as $opt)
      <a href="{{ route('statistics', ['range' => $opt]) }}"
         class="btn btn-sm {{ $days === $opt ? 'btn-dark' : 'btn-outline-secondary' }}">
        Last {{ $opt }} days
      </a>
    @endforeach
  </div>
</div>

{{-- KPI tiles --}}
<div class="row g-3 mb-2">
  <div class="col-12 col-sm-6 col-lg-3">
    <div class="tile">
      <h3>In Range</h3>
      <div class="d-flex align-items-end justify-content-between">
        <div class="value">{{ number_format($totals['range']) }}</div>
        <svg class="sparkline" viewBox="0 0 100 28" preserveAspectRatio="none">
          @php
            $m = max(1, $max);
            $sn = max(2, $n);
            $sp = [];
            foreach ($series as $i => $row) {
              $sx = ($i / ($sn - 1)) * 100;
              $sy = 28 - ($row['count'] / $m) * 24 - 2;
              $sp[] = round($sx,1).','.round($sy,1);
            }
          @endphp
          <polyline fill="none" stroke="#06b6d4" stroke-width="2" points="{{ implode(' ', $sp) }}"/>
        </svg>
      </div>
    </div>
  </div>
  <div class="col-12 col-sm-6 col-lg-3">
    <div class="tile">
      <h3>Yesterday</h3>
      <div class="d-flex align-items-end justify-content-between">
        <div class="value">{{ number_format($totals['yesterday']) }}</div>
        <svg class="sparkline" viewBox="0 0 100 28" preserveAspectRatio="none">
          <polyline fill="none" stroke="#22c55e" stroke-width="2"
            points="2,22 15,18 28,16 41,12 54,16 67,14 80,12 93,16"/>
        </svg>
      </div>
    </div>
  </div>
  <div class="col-12 col-sm-6 col-lg-3">
    <div class="tile">
      <h3>Last 7 Days</h3>
      <div class="d-flex align-items-end justify-content-between">
        <div class="value">{{ number_format($totals['last7']) }}</div>
        <svg class="sparkline" viewBox="0 0 100 28" preserveAspectRatio="none">
          <polyline fill="none" stroke="#16a34a" stroke-width="2"
            points="2,20 15,18 28,14 41,16 54,12 67,8 80,12 93,10"/>
        </svg>
      </div>
    </div>
  </div>
  <div class="col-12 col-sm-6 col-lg-3">
    <div class="tile">
      <h3>All Time</h3>
      <div class="d-flex align-items-end justify-content-between">
        <div class="value">{{ number_format($totals['all']) }}</div>
        <svg class="sparkline" viewBox="0 0 100 28" preserveAspectRatio="none">
          <polyline fill="none" stroke="#f59e0b" stroke-width="2"
            points="2,22 15,20 28,18 41,16 54,14 67,12 80,10 93,12"/>
        </svg>
      </div>
    </div>
  </div>
</div>

<div class="row g-3">
  {{-- Downloads chart --}}
  <div class="col-lg-8">
    <div class="section-card p-3">
      <div class="d-flex align-items-center justify-content-between mb-2">
        <div>
          <h5 class="mb-0">Downloads (daily)</h5>
          <small class="text-secondary">{{ $from->toFormattedDateString() }} – {{ $to->toFormattedDateString() }}</small>
        </div>
      </div>

      <div class="p-2">
        <svg viewBox="0 0 {{ $W }} {{ $H }}" class="area-chart" preserveAspectRatio="none" style="width:100%;height:260px;display:block">
          {{-- grid --}}
          <g stroke="rgba(0,0,0,.06)" stroke-width="1">
            <line x1="0" y1="{{ $pad + $usableH }}" x2="{{ $W }}" y2="{{ $pad + $usableH }}"/>
            <line x1="0" y1="{{ $pad + $usableH*0.66 }}" x2="{{ $W }}" y2="{{ $pad + $usableH*0.66 }}"/>
            <line x1="0" y1="{{ $pad + $usableH*0.33 }}" x2="{{ $W }}" y2="{{ $pad + $usableH*0.33 }}"/>
          </g>

          <defs>
            <linearGradient id="areaFillStats" x1="0" y1="0" x2="0" y2="1">
              <stop offset="0%" stop-color="#22c55e" stop-opacity=".35"/>
              <stop offset="100%" stop-color="#22c55e" stop-opacity="0"/>
            </linearGradient>
          </defs>

          {{-- area as polygon (simple) --}}
          <polygon points="{{ $polyFill }}" fill="url(#areaFillStats)"/>
          {{-- line on top --}}
          <polyline points="{{ implode(' ', $points) }}" fill="none" stroke="#22c55e" stroke-width="3"/>
        </svg>
      </div>
    </div>

    {{-- Top Episodes --}}
    <div class="section-card p-3 mt-3">
      <div class="d-flex align-items-center justify-content-between">
        <h5 class="mb-0">Top Episodes (last {{ $days }} days)</h5>
      </div>
      <div class="table-responsive mt-2">
        <table class="table align-middle">
          <thead class="table-light">
            <tr>
              <th>Episode</th>
              <th class="text-end">Downloads</th>
            </tr>
          </thead>
          <tbody>
          @forelse($topEpisodes as $row)
            <tr>
              <td class="fw-medium">{{ $row->title }}</td>
              <td class="text-end">{{ number_format($row->downloads) }}</td>
            </tr>
          @empty
            <tr><td colspan="2" class="text-center text-secondary py-4">No downloads in this range.</td></tr>
          @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  {{-- Right rail: quick facts --}}
  <div class="col-lg-4">
    <div class="section-card p-3">
      <h5 class="mb-3">Quick Facts</h5>
      <ul class="list-unstyled mb-0">
        <li class="d-flex justify-content-between py-1 border-bottom">
          <span>Max daily downloads</span>
          <strong>
            @php
              $maxDay = collect($series)->sortByDesc('count')->first();
            @endphp
            {{ $maxDay['count'] ?? 0 }}
          </strong>
        </li>
        <li class="d-flex justify-content-between py-1 border-bottom">
          <span>Average per day</span>
          <strong>
            @php $avg = $n ? round(array_sum(array_column($series,'count')) / $n) : 0; @endphp
            {{ $avg }}
          </strong>
        </li>
        <li class="d-flex justify-content-between py-1">
          <span>Range total</span>
          <strong>{{ number_format($totals['range']) }}</strong>
        </li>
      </ul>
    </div>

    <div class="section-card p-3 mt-3">
      <h6 class="mb-2">Tips</h6>
      <ul class="text-secondary small mb-0">
        <li>Use the date filter to compare different windows.</li>
        <li>Click “Distribution” to submit your RSS to more platforms.</li>
        <li>Use “Episodes” to publish and track new content quickly.</li>
      </ul>
    </div>
  </div>
</div>
@endsection
