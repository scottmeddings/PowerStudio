{{-- resources/views/pages/test_totals.blade.php --}}
@extends('layouts.app')

@section('title', 'Test Totals')
@section('page-title', 'test totals')

@section('content')
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Database Totals</h5>
    <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-sm">
      <i class="bi bi-arrow-left-short"></i> Back to Dashboard
    </a>
  </div>

  {{-- High-level tiles --}}
  <div class="row g-3">
    <div class="col-12 col-sm-6 col-lg-3">
      <div class="tile">
        <h3>Users</h3>
        <div class="d-flex align-items-end justify-content-between">
          <div class="value">{{ number_format($totals['users'] ?? 0) }}</div>
          <i class="bi bi-people"></i>
        </div>
      </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
      <div class="tile">
        <h3>Episodes</h3>
        <div class="d-flex align-items-end justify-content-between">
          <div class="value">{{ number_format($totals['episodes'] ?? 0) }}</div>
          <i class="bi bi-mic"></i>
        </div>
      </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
      <div class="tile">
        <h3>Downloads (All)</h3>
        <div class="d-flex align-items-end justify-content-between">
          <div class="value">{{ number_format($totals['downloads'] ?? 0) }}</div>
          <i class="bi bi-cloud-download"></i>
        </div>
      </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
      <div class="tile">
        <h3>Downloads 30d</h3>
        <div class="d-flex align-items-end justify-content-between">
          <div class="value">{{ number_format($totals['last30'] ?? ($totals['downloads_last30'] ?? 0)) }}</div>
          <i class="bi bi-bar-chart"></i>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-3 mt-1">
    {{-- Episodes by status --}}
    <div class="col-lg-6">
      <div class="section-card p-3">
        <div class="d-flex align-items-center justify-content-between mb-1">
          <h6 class="mb-0">Episodes by Status</h6>
          <span class="text-secondary small">
            total: {{ number_format($totals['episodes'] ?? 0) }}
          </span>
        </div>
        <div class="table-responsive mt-2">
          <table class="table align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Status</th>
                <th class="text-end">Count</th>
              </tr>
            </thead>
            <tbody>
              @forelse($episodesByStatus as $row)
                <tr>
                  <td class="text-capitalize">{{ $row->status ?? 'unknown' }}</td>
                  <td class="text-end">{{ number_format($row->c ?? 0) }}</td>
                </tr>
              @empty
                <tr><td colspan="2" class="text-secondary text-center py-3">No data or column missing.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    {{-- Top episodes by downloads --}}
    <div class="col-lg-6">
      <div class="section-card p-3">
        <div class="d-flex align-items-center justify-content-between mb-1">
          <h6 class="mb-0">Top Episodes by Downloads</h6>
          <span class="text-secondary small">
            7d: {{ number_format($totals['downloads_last7'] ?? 0) }} · 30d: {{ number_format($totals['downloads_last30'] ?? 0) }}
          </span>
        </div>
        <div class="table-responsive mt-2">
          <table class="table align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Title</th>
                <th class="text-end">Downloads</th>
              </tr>
            </thead>
            <tbody>
              @forelse($topEpisodes as $row)
                <tr>
                  <td class="text-truncate" style="max-width: 420px;">{{ $row->title }}</td>
                  <td class="text-end">{{ number_format($row->downloads_count) }}</td>
                </tr>
              @empty
                <tr><td colspan="2" class="text-secondary text-center py-3">No downloads or missing episode_id.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    {{-- Table counts (DB-wide) --}}
    <div class="col-12">
      <div class="section-card p-3">
        <div class="d-flex align-items-center justify-content-between mb-1">
          <h6 class="mb-0">All Tables — Row Counts</h6>
          @if($tableCountsError)
            <span class="badge text-bg-warning">Note</span>
          @endif
        </div>
        @if($tableCountsError)
          <div class="small text-secondary mb-2">
            Best-effort listing ({{ $tableCountsError }}). Unsupported drivers will show nothing here.
          </div>
        @endif
        <div class="table-responsive mt-2">
          <table class="table align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Table</th>
                <th class="text-end">Rows</th>
              </tr>
            </thead>
            <tbody>
              @forelse($tableCounts as $name => $count)
                <tr>
                  <td class="font-monospace">{{ $name }}</td>
                  <td class="text-end">{{ is_numeric($count) ? number_format($count) : $count }}</td>
                </tr>
              @empty
                <tr><td colspan="2" class="text-secondary text-center py-3">No table data available.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
@endsection
