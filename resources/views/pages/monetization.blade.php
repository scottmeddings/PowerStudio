{{-- resources/views/pages/monetization.blade.php --}}
@extends('layouts.app')

@section('title', 'Monetization')
@section('page-title', 'monetization')

@section('content')
@php
  // Safe defaults if controller doesn't pass data yet
  $rev = $rev ?? [
    'mtd'    => 0,
    'last30' => 0,
    'all'    => 0,
    'ecpm'   => 0,
  ];
  $payouts = $payouts ?? []; // [['date'=>'2025-08-31','amount'=>120.25,'status'=>'paid'], ...]
@endphp

<style>
  .mini { font-size:.875rem; color:#64748b; }
  .tile h3{ font-size:.9rem; color:#64748b; margin:0 0 .3rem; }
  .tile .value{ font-weight:700; font-size:1.6rem; letter-spacing:.02em; }
  .platform-card{
    background:#fff;border:1px solid rgba(0,0,0,.06);border-radius:.75rem;padding:1rem;
  }
  .platform-card .icon{
    width:40px;height:40px;border-radius:10px;display:grid;place-items:center;color:#fff;margin-right:.75rem;
  }
  .i-stripe{background:#635bff}
  .i-acast{background:#111}
  .i-mega{background:#1db954}
  .i-dynamic{background:#0ea5e9}
</style>

{{-- KPI tiles --}}
<div class="row g-3 mb-3">
  <div class="col-12 col-sm-6 col-lg-3">
    <div class="tile">
      <h3>MTD Revenue</h3>
      <div class="d-flex align-items-end justify-content-between">
        <div class="value">${{ number_format($rev['mtd'], 2) }}</div>
        <svg class="sparkline" viewBox="0 0 100 28" preserveAspectRatio="none">
          <polyline fill="none" stroke="#16a34a" stroke-width="2"
            points="2,22 15,20 28,16 41,14 54,12 67,10 80,9 93,8"/>
        </svg>
      </div>
    </div>
  </div>
  <div class="col-12 col-sm-6 col-lg-3">
    <div class="tile">
      <h3>Last 30 Days</h3>
      <div class="d-flex align-items-end justify-content-between">
        <div class="value">${{ number_format($rev['last30'], 2) }}</div>
        <svg class="sparkline" viewBox="0 0 100 28" preserveAspectRatio="none">
          <polyline fill="none" stroke="#06b6d4" stroke-width="2"
            points="2,22 15,18 28,16 41,14 54,18 67,16 80,14 93,12"/>
        </svg>
      </div>
    </div>
  </div>
  <div class="col-12 col-sm-6 col-lg-3">
    <div class="tile">
      <h3>All Time</h3>
      <div class="d-flex align-items-end justify-content-between">
        <div class="value">${{ number_format($rev['all'], 2) }}</div>
        <svg class="sparkline" viewBox="0 0 100 28" preserveAspectRatio="none">
          <polyline fill="none" stroke="#f59e0b" stroke-width="2"
            points="2,22 15,21 28,20 41,18 54,16 67,13 80,11 93,10"/>
        </svg>
      </div>
    </div>
  </div>
  <div class="col-12 col-sm-6 col-lg-3">
    <div class="tile">
      <h3>eCPM</h3>
      <div class="d-flex align-items-end justify-content-between">
        <div class="value">${{ number_format($rev['ecpm'], 2) }}</div>
        <svg class="sparkline" viewBox="0 0 100 28" preserveAspectRatio="none">
          <polyline fill="none" stroke="#22c55e" stroke-width="2"
            points="2,18 15,16 28,14 41,12 54,13 67,11 80,10 93,9"/>
        </svg>
      </div>
    </div>
  </div>
</div>

<div class="row g-3">
  {{-- Left column --}}
  <div class="col-lg-8">

    {{-- CPM Calculator --}}
    <div class="section-card p-3">
      <div class="d-flex align-items-center justify-content-between mb-2">
        <h5 class="mb-0">CPM Calculator</h5>
        <span class="mini">Quick estimate — not a contract, obviously.</span>
      </div>

      <div class="row g-3 align-items-end">
        <div class="col-12 col-sm-6 col-xl-3">
          <label class="form-label">Downloads</label>
          <input id="calcDownloads" type="number" min="0" class="form-control" placeholder="25000" value="25000">
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
          <label class="form-label">Fill rate (%)</label>
          <input id="calcFill" type="number" min="0" max="100" class="form-control" placeholder="70" value="70">
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
          <label class="form-label">CPM (USD)</label>
          <input id="calcCPM" type="number" min="0" step="0.01" class="form-control" placeholder="18" value="18">
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
          <button id="calcBtn" type="button" class="btn btn-dark w-100">
            <i class="bi bi-calculator me-1"></i>Estimate
          </button>
        </div>
      </div>

      <div class="mt-3">
        <div id="calcResult" class="alert alert-secondary mb-0" role="alert">
          Estimated revenue: <strong>$0.00</strong>
        </div>
      </div>
    </div>

    {{-- Inventory (per-episode ad slots) --}}
    <div class="section-card p-3 mt-3">
      <div class="d-flex align-items-center justify-content-between mb-2">
        <h5 class="mb-0">Ad Inventory</h5>
        <span class="mini">Pre/Mid/Post-roll per episode.</span>
      </div>

      <div class="table-responsive">
        <table class="table align-middle">
          <thead class="table-light">
            <tr>
              <th>Episode</th>
              <th class="text-center">Pre</th>
              <th class="text-center">Mid</th>
              <th class="text-center">Post</th>
              <th class="text-end">Status</th>
            </tr>
          </thead>
          <tbody>
            {{-- Replace with real data when ready --}}
            <tr>
              <td>E19: Power Platform with Ryan Cunningham</td>
              <td class="text-center">1/2</td>
              <td class="text-center">2/3</td>
              <td class="text-center">0/1</td>
              <td class="text-end"><span class="badge text-bg-success">Selling</span></td>
            </tr>
            <tr>
              <td>Kids Helpline – Tracy Adams (Special)</td>
              <td class="text-center">0/2</td>
              <td class="text-center">1/2</td>
              <td class="text-center">0/1</td>
              <td class="text-end"><span class="badge text-bg-secondary">Paused</span></td>
            </tr>
            <tr>
              <td>Intro to Powerpod</td>
              <td class="text-center">0/1</td>
              <td class="text-center">0/1</td>
              <td class="text-center">0/1</td>
              <td class="text-end"><span class="badge text-bg-warning">Draft</span></td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="text-end">
        <button type="button" class="btn btn-outline-secondary btn-sm">Manage inventory</button>
      </div>
    </div>

    {{-- Payouts --}}
    <div class="section-card p-3 mt-3">
      <div class="d-flex align-items-center justify-content-between mb-2">
        <h5 class="mb-0">Payouts</h5>
        <span class="mini">Paid monthly after thresholds.</span>
      </div>

      <div class="table-responsive">
        <table class="table align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Date</th>
              <th>Status</th>
              <th class="text-end">Amount</th>
            </tr>
          </thead>
          <tbody>
          @forelse($payouts as $p)
            <tr>
              <td>{{ \Illuminate\Support\Carbon::parse($p['date'])->toFormattedDateString() }}</td>
              <td>
                @php $st = strtolower($p['status']); @endphp
                <span class="badge text-bg-{{ $st==='paid'?'success':($st==='processing'?'warning':'secondary') }}">
                  {{ ucfirst($p['status']) }}
                </span>
              </td>
              <td class="text-end">${{ number_format($p['amount'], 2) }}</td>
            </tr>
          @empty
            <tr><td colspan="3" class="text-center text-secondary py-4">No payouts yet.</td></tr>
          @endforelse
          </tbody>
        </table>
      </div>
    </div>

  </div>

  {{-- Right column --}}
  <div class="col-lg-4">

    {{-- Monetization providers --}}
    <div class="section-card p-3">
      <h5 class="mb-3">Providers</h5>

      <div class="platform-card d-flex align-items-center mb-2">
        <div class="icon i-stripe">
          <i class="bi bi-credit-card-2-front"></i>
        </div>
        <div>
          <div class="fw-semibold">Stripe Connect</div>
          <div class="mini">Direct listener support & payouts.</div>
        </div>
        <div class="ms-auto">
          <button class="btn btn-dark btn-sm" type="button">Connect</button>
        </div>
      </div>

      <div class="platform-card d-flex align-items-center mb-2">
        <div class="icon i-mega">
          <i class="bi bi-broadcast-pin"></i>
        </div>
        <div>
          <div class="fw-semibold">Dynamic Ad Insertion</div>
          <div class="mini">Pre/Mid/Post-roll marketplace.</div>
        </div>
        <div class="ms-auto">
          <button class="btn btn-outline-secondary btn-sm" type="button">Configure</button>
        </div>
      </div>

      <div class="platform-card d-flex align-items-center">
        <div class="icon i-dynamic">
          <i class="bi bi-mic"></i>
        </div>
        <div>
          <div class="fw-semibold">Sponsorships</div>
          <div class="mini">Manual host-read campaigns.</div>
        </div>
        <div class="ms-auto">
          <button class="btn btn-outline-secondary btn-sm" type="button">Create offer</button>
        </div>
      </div>
    </div>

    {{-- House ads / cross-promo --}}
    <div class="section-card p-3 mt-3">
      <h6 class="mb-2">House Ads</h6>
      <p class="mini">Fill unused inventory with your own promotions.</p>
      <div class="d-grid gap-2">
        <button class="btn btn-outline-secondary btn-sm" type="button">New house campaign</button>
        <button class="btn btn-outline-secondary btn-sm" type="button">Import promo set</button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  (function(){
    const fmt = v => '$' + (Math.round(v * 100) / 100).toFixed(2);
    const $d  = document.getElementById('calcDownloads');
    const $f  = document.getElementById('calcFill');
    const $c  = document.getElementById('calcCPM');
    const $r  = document.getElementById('calcResult');
    const calc = () => {
      const d = Math.max(0, parseFloat($d.value) || 0);
      const f = Math.min(100, Math.max(0, parseFloat($f.value) || 0));
      const c = Math.max(0, parseFloat($c.value) || 0);
      const impressions = d * (f/100);
      const revenue = impressions / 1000 * c;
      $r.innerHTML = 'Estimated revenue: <strong>' + fmt(revenue) + '</strong>';
    };
    document.getElementById('calcBtn')?.addEventListener('click', calc);
  })();
</script>
@endpush
