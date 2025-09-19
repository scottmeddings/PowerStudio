{{-- resources/views/pages/monetization.blade.php --}}
@extends('layouts.app')

@section('title', 'Monetization')
@section('page-title', 'monetization')

@section('content')
@php
  $rev = $rev ?? ['mtd'=>0,'last30'=>0,'all'=>0,'ecpm'=>0];
  $payouts = $payouts ?? [];
  $inventory = $inventory ?? null;
  $stripe_connected = $stripe_connected ?? false;
@endphp

<style>
  .mini { font-size:.875rem; color:#64748b; }
  .tile h3{ font-size:.9rem; color:#64748b; margin:0 0 .3rem; }
  .tile .value{ font-weight:700; font-size:1.6rem; letter-spacing:.02em; }
  .platform-card{ background:#fff;border:1px solid rgba(0,0,0,.06);border-radius:.75rem;padding:1rem; }
  .platform-card .icon{ width:40px;height:40px;border-radius:10px;display:grid;place-items:center;color:#fff;margin-right:.75rem; }
  .i-stripe{background:#635bff}
  .i-mega{background:#1db954}
  .i-dynamic{background:#0ea5e9}
  .sparkline{ width:120px;height:28px;margin-left:.5rem; }
</style>

{{-- KPI tiles --}}
<div class="row g-3 mb-3">
  <div class="col-12 col-sm-6 col-lg-3">
    <div class="tile">
      <h3>MTD Revenue</h3>
      <div class="d-flex align-items-end justify-content-between">
        <div class="value">${{ number_format($rev['mtd'], 2) }}</div>
        <canvas id="spark-mtd" class="sparkline"></canvas>
      </div>
    </div>
  </div>
  <div class="col-12 col-sm-6 col-lg-3">
    <div class="tile">
      <h3>Last 30 Days</h3>
      <div class="d-flex align-items-end justify-content-between">
        <div class="value">${{ number_format($rev['last30'], 2) }}</div>
        <canvas id="spark-30" class="sparkline"></canvas>
      </div>
    </div>
  </div>
  <div class="col-12 col-sm-6 col-lg-3">
    <div class="tile">
      <h3>All Time</h3>
      <div class="d-flex align-items-end justify-content-between">
        <div class="value">${{ number_format($rev['all'], 2) }}</div>
        <canvas id="spark-all" class="sparkline"></canvas>
      </div>
    </div>
  </div>
  <div class="col-12 col-sm-6 col-lg-3">
    <div class="tile">
      <h3>eCPM</h3>
      <div class="d-flex align-items-end justify-content-between">
        <div class="value">${{ number_format($rev['ecpm'], 2) }}</div>
        <canvas id="spark-ecpm" class="sparkline"></canvas>
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
        <div class="col-12 col-sm-6 col-xl-3 d-flex gap-2">
          <button id="calcBtn" type="button" class="btn btn-dark w-100">
            <i class="bi bi-calculator me-1"></i>Estimate
          </button>
          <button id="calcPersistBtn" type="button" class="btn btn-outline-secondary" title="Save to today for charts">
            <i class="bi bi-save"></i>
          </button>
        </div>
      </div>

      <div class="mt-3">
        <div id="calcResult" class="alert alert-secondary mb-0" role="alert">
          Estimated revenue: <strong>$0.00</strong>
        </div>
      </div>
    </div>

    {{-- Revenue Charts --}}
    <div class="section-card p-3 mt-3">
      <div class="d-flex align-items-center justify-content-between mb-2">
        <h5 class="mb-0">Revenue (Last 180 Days)</h5>
      </div>
      <canvas id="revChart" height="110"></canvas>
    </div>

    <div class="section-card p-3 mt-3">
      <div class="d-flex align-items-center justify-content-between mb-2">
        <h5 class="mb-0">Downloads & eCPM</h5>
      </div>
      <canvas id="mixChart" height="110"></canvas>
    </div>

    {{-- Inventory --}}
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
          @if($inventory && count($inventory))
            @foreach($inventory as $row)
              @php
                $statusMap = ['selling'=>'success','paused'=>'secondary','draft'=>'warning'];
                $badge = $statusMap[strtolower($row->status ?? 'draft')] ?? 'secondary';
              @endphp
              <tr>
                <td>{{ $row->episode_title ?? ('Episode #'.$row->episode_id) }}</td>
                <td class="text-center">{{ ($row->pre_sold ?? 0) }}/{{ ($row->pre_total ?? 0) }}</td>
                <td class="text-center">{{ ($row->mid_sold ?? 0) }}/{{ ($row->mid_total ?? 0) }}</td>
                <td class="text-center">{{ ($row->post_sold ?? 0) }}/{{ ($row->post_total ?? 0) }}</td>
                <td class="text-end"><span class="badge text-bg-{{ $badge }}">{{ ucfirst($row->status ?? 'draft') }}</span></td>
              </tr>
            @endforeach
          @else
            {{-- Demo rows (fallback) --}}
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
          @endif
          </tbody>
        </table>
      </div>
      <div class="text-end">
        @php use Illuminate\Support\Facades\Route as RouteFacade; @endphp
        @if (RouteFacade::has('episodes.index'))
          <a href="{{ route('episodes.index') }}" class="btn btn-outline-secondary btn-sm">Manage inventory</a>
        @else
          <a href="{{ url('/episodes') }}" class="btn btn-outline-secondary btn-sm">Manage inventory</a>
        @endif
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
            @php $st = strtolower($p['status']); @endphp
            <tr>
              <td>{{ \Illuminate\Support\Carbon::parse($p['date'])->toFormattedDateString() }}</td>
              <td>
                <span class="badge text-bg-{{ in_array($st,['paid'])?'success':(in_array($st,['processing','in_transit'])?'warning':'secondary') }}">
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
      <div class="d-flex align-items-center justify-content-between mb-2">
        <h5 class="mb-0">Providers</h5>
        @if($stripe_connected)
          <span class="badge text-bg-success">Stripe connected</span>
        @endif
      </div>

      {{-- Stripe --}}
      <div class="platform-card d-flex align-items-center mb-2">
        <div class="icon i-stripe">
          <i class="bi bi-credit-card-2-front"></i>
        </div>
        <div>
          <div class="fw-semibold">Stripe Connect</div>
          <div class="mini">Direct listener support & payouts.</div>
        </div>
        <div class="ms-auto d-flex gap-2">
          <form method="POST" action="{{ route('monetization.stripe.connect') }}">@csrf
            <button class="btn btn-dark btn-sm" type="submit">{{ $stripe_connected?'Reconnect':'Connect' }}</button>
          </form>
          <form method="POST" action="{{ route('monetization.stripe.refresh') }}">@csrf
            <button class="btn btn-outline-secondary btn-sm" type="submit">Sync now</button>
          </form>
        </div>
      </div>

      {{-- Dynamic Ad Insertion --}}
      <div class="platform-card d-flex align-items-center mb-2">
        <div class="icon i-mega">
          <i class="bi bi-broadcast-pin"></i>
        </div>
        <div>
          <div class="fw-semibold">Dynamic Ad Insertion</div>
          <div class="mini">Pre/Mid/Post-roll marketplace.</div>
        </div>
        <div class="ms-auto">
          @php use Illuminate\Support\Facades\Route as RF; @endphp
          @if (RF::has('monetization.dynamic.show'))
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('monetization.dynamic.show') }}">Configure</a>
          @else
            <a class="btn btn-outline-secondary btn-sm" href="{{ url('/monetization/dynamic') }}">Configure</a>
          @endif
        </div>
      </div>

      {{-- Sponsorships --}}
      <div class="platform-card d-flex align-items-center">
        <div class="icon i-dynamic">
          <i class="bi bi-mic"></i>
        </div>
        <div>
          <div class="fw-semibold">Sponsorships</div>
          <div class="mini">Manual host-read campaigns.</div>
        </div>
        <div class="ms-auto">
          @if (RF::has('monetization.sponsorships.new'))
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('monetization.sponsorships.new') }}">Create offer</a>
          @else
            <a class="btn btn-outline-secondary btn-sm" href="{{ url('/monetization/sponsorships/new') }}">Create offer</a>
          @endif
        </div>
      </div>
    </div>

    {{-- House ads / cross-promo --}}
    <div class="section-card p-3 mt-3">
      <h6 class="mb-2">House Ads</h6>
      <p class="mini">Fill unused inventory with your own promotions.</p>
      <div class="d-grid gap-2">
        @if (RF::has('monetization.house.new'))
          <a class="btn btn-outline-secondary btn-sm" href="{{ route('monetization.house.new') }}">New house campaign</a>
        @else
          <a class="btn btn-outline-secondary btn-sm" href="{{ url('/monetization/house/new') }}">New house campaign</a>
        @endif
      </div>
      {{-- Import & JSON helper removed per request --}}
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js" defer></script>
<script>
/* existing JS unchanged – calculator, charts, sparklines */
</script>
@endpush
