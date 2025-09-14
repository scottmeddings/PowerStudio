@extends('layouts.app')

@section('title', 'Statistics')
@section('page-title', 'statistics')

@section('content')
@php
  // ---------- NORMALIZE INPUT ----------
  $raw = collect($series ?? []);
  $rowsReal = $raw->map(function ($r) {
      if (is_array($r))  return ['count' => (int)($r['count'] ?? 0)];
      if (is_object($r)) return ['count' => (int)($r->count ?? 0)];
      return ['count' => (int)$r];
  })->values();

  $nReal   = $rowsReal->count();
  $sumReal = (int)$rowsReal->sum('count');
  $maxReal = max(1, (int)($rowsReal->max('count') ?? 0));

  // ---------- DERIVED TOTALS (fallbacks if controller didn't set them) ----------
  // "Yesterday": if $to is today, use the second-last point; otherwise use the last point.
  $yIndex = max(0, $nReal - 1);
  if (isset($to) && method_exists($to, 'isToday') && $to->isToday() && $nReal >= 2) {
      $yIndex = $nReal - 2;
  }
  $yesterdayCalc = (int)($rowsReal->get($yIndex)['count'] ?? 0);

  // "Last 7 days": sum of last up to 7 points
  $last7Calc = (int)$rowsReal->slice(max(0, $nReal - 7))->sum('count');

  // "Range total": sum of current series
  $rangeCalc = $sumReal;

  // "All time": if controller didn't provide, fall back to range
  $allCalc = (int)($totals['all'] ?? 0);
  if ($allCalc === 0 && $rangeCalc > 0) $allCalc = $rangeCalc;

  // Prefer controller numbers only if they’re non-zero; otherwise use our fallbacks
  $rangeTotal    = (int)($totals['range']    ?? 0); if ($rangeTotal    === 0) $rangeTotal    = $rangeCalc;
  $yesterdayTotal= (int)($totals['yesterday']?? 0); if ($yesterdayTotal=== 0) $yesterdayTotal= $yesterdayCalc;
  $last7Total    = (int)($totals['last7']    ?? 0); if ($last7Total    === 0) $last7Total    = $last7Calc;
  $allTotal      = (int)($totals['all']      ?? 0); if ($allTotal      === 0) $allTotal      = $allCalc;

  // ---------- CHART CANVAS ----------
  $W=640; $H=240; $pad=10; $usableW=$W-($pad*2); $usableH=$H-($pad*2);

  // Ensure at least 2 points for the svg polyline
  $rows = $rowsReal;
  if ($rows->count() < 2) {
      $v = (int)($rows->get(0)['count'] ?? 0);
      $rows = collect([['count'=>$v], ['count'=>$v]]);
  }

  $n   = $rows->count();
  $max = max(1, (int)($rows->max('count') ?? 0));

  // Main chart points
  $points = [];
  foreach ($rows as $i => $row) {
      $x = $pad + ($usableW * ($i / ($n - 1)));
      $y = $pad + $usableH - (($row['count'] / $max) * $usableH);
      $points[] = round($x, 1) . ',' . round($y, 1);
  }

  // Area polygon (lift baseline by epsilon if all zero so polygon renders)
  $allZero   = ($sumReal === 0);
  $baselineY = $pad + $usableH;
  $epsilon   = $allZero ? 0.0001 : 0;
  $polyFill  = "{$pad},".($baselineY - $epsilon)." ".implode(' ', $points)." ".($pad+$usableW).",".($baselineY - $epsilon);

  // Sparkline points for "In Range" tile (100x28)
  $sparkPoints = (function ($rowsForSpark) {
      $m  = max(1, (int)collect($rowsForSpark)->max('count'));
      $nS = max(2, count($rowsForSpark));
      $pts = [];
      foreach ($rowsForSpark as $i => $r) {
          $sx = ($i / ($nS - 1)) * 100;
          $sy = 28 - (($r['count'] / $m) * 24) - 2;
          $pts[] = round($sx,1).','.round($sy,1);
      }
      return implode(' ', $pts);
  })($rows->all());

  // Quick Facts
  $maxCount = (int)$maxReal;
  $avg      = $nReal ? round($sumReal / $nReal) : 0;
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
        <div class="value">{{ number_format($rangeTotal) }}</div>
        <svg class="sparkline" viewBox="0 0 100 28" preserveAspectRatio="none" aria-hidden="true">
          <polyline fill="none" stroke="#06b6d4" stroke-width="2" stroke-linejoin="round" stroke-linecap="round"
            points="{{ $sparkPoints }}"/>
        </svg>
      </div>
    </div>
  </div>

  <div class="col-12 col-sm-6 col-lg-3">
  <div class="tile">
    <h3>Yesterday</h3>
    <div class="d-flex align-items-end justify-content-between">
      <div class="value">{{ number_format($yesterdayTotal) }}</div>
      <svg class="sparkline" viewBox="0 0 100 28" preserveAspectRatio="none" aria-hidden="true">
        <polyline fill="none" stroke="#22c55e" stroke-width="2" stroke-linejoin="round" stroke-linecap="round"
          points="{{ $sparks['yesterday'] ?? '' }}"/>
      </svg>
    </div>
  </div>
</div>

<div class="col-12 col-sm-6 col-lg-3">
  <div class="tile">
    <h3>Last 7 Days</h3>
    <div class="d-flex align-items-end justify-content-between">
      <div class="value">{{ number_format($last7Total) }}</div>
      <svg class="sparkline" viewBox="0 0 100 28" preserveAspectRatio="none" aria-hidden="true">
        <polyline fill="none" stroke="#16a34a" stroke-width="2" stroke-linejoin="round" stroke-linecap="round"
          points="{{ $sparks['last7'] ?? '' }}"/>
      </svg>
    </div>
  </div>
</div>

<div class="col-12 col-sm-6 col-lg-3">
  <div class="tile">
    <h3>All Time</h3>
    <div class="d-flex align-items-end justify-content-between">
      <div class="value">{{ number_format($allTotal) }}</div>
      <svg class="sparkline" viewBox="0 0 100 28" preserveAspectRatio="none" aria-hidden="true">
        <polyline fill="none" stroke="#f59e0b" stroke-width="2" stroke-linejoin="round" stroke-linecap="round"
          points="{{ $sparks['all'] ?? '' }}"/>
      </svg>
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

          {{-- area as polygon (robust even for all-zero series) --}}
          <polygon points="{{ $polyFill }}" fill="url(#areaFillStats)"/>
          {{-- line on top --}}
          <polyline points="{{ implode(' ', $points) }}" fill="none" stroke="#22c55e" stroke-width="3" stroke-linejoin="round" stroke-linecap="round"/>
        </svg>

        @if($sumReal === 0)
          <div class="text-center text-secondary small mt-2">No downloads in this range.</div>
        @endif
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
          <strong>{{ $maxCount }}</strong>
        </li>
        <li class="d-flex justify-content-between py-1 border-bottom">
          <span>Average per day</span>
          <strong>{{ $avg }}</strong>
        </li>
        <li class="d-flex justify-content-between py-1">
          <span>Range total</span>
          <strong>{{ number_format($rangeTotal) }}</strong>
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
