    
{{-- resources/views/components/stats-boxes.blade.php --}}
@php
  $yesterday = $metrics['yesterday'] ?? 0;
  $last7     = $metrics['last7']     ?? 0;
  $last30    = $metrics['last30']    ?? 0;
  $allTime   = $metrics['all'] ?? ($metrics['allTime'] ?? 0);
@endphp

<style>
  /* keep styles local so it works anywhere this is included */
  .tile{background:#fff;border:1px solid rgba(0,0,0,.06);border-radius:.75rem;padding:1rem}
  .tile h3{font-size:.95rem;color:#64748b;margin:0 0 .35rem}
  .tile .value{font-size:2rem;font-weight:700;line-height:1}
  .sparkline{width:140px;height:40px}
</style>

<div class="row g-3">
  <div class="col-12 col-sm-6 col-xl-3">
    <div class="tile">
      <h3>Yesterday Downloads</h3>
      <div class="d-flex align-items-end justify-content-between">
        <div class="value">{{ number_format($yesterday) }}</div>
        <svg class="sparkline" viewBox="0 0 100 28" preserveAspectRatio="none">
          <polyline fill="none" stroke="#22c55e" stroke-width="2"
            points="2,18 15,12 28,16 41,24 54,10 67,12 80,8 98,20"/>
        </svg>
      </div>
    </div>
  </div>

  <div class="col-12 col-sm-6 col-xl-3">
    <div class="tile">
      <h3>Last 7 Days Downloads</h3>
      <div class="d-flex align-items-end justify-content-between">
        <div class="value">{{ number_format($last7) }}</div>
        <svg class="sparkline" viewBox="0 0 100 28" preserveAspectRatio="none">
          <polyline fill="none" stroke="#16a34a" stroke-width="2"
            points="2,20 15,18 28,14 41,16 54,12 67,8 80,12 98,10"/>
        </svg>
      </div>
    </div>
  </div>

  <div class="col-12 col-sm-6 col-xl-3">
    <div class="tile">
      <h3>Last 30 Days Downloads</h3>
      <div class="d-flex align-items-end justify-content-between">
        <div class="value">{{ number_format($last30) }}</div>
        <svg class="sparkline" viewBox="0 0 100 28" preserveAspectRatio="none">
          <polyline fill="none" stroke="#06b6d4" stroke-width="2"
            points="2,22 15,16 28,14 41,10 54,16 67,14 80,12 98,16"/>
        </svg>
      </div>
    </div>
  </div>

  <div class="col-12 col-sm-6 col-xl-3">
    <div class="tile">
      <h3>All Time Downloads</h3>
      <div class="d-flex align-items-end justify-content-between">
        <div class="value">{{ number_format($allTime) }}</div>
        <svg class="sparkline" viewBox="0 0 100 28" preserveAspectRatio="none">
          <polyline fill="none" stroke="#f59e0b" stroke-width="2"
            points="2,22 25,21 52,18 80,10 98,12"/>
        </svg>
      </div>
    </div>
  </div>
</div>
