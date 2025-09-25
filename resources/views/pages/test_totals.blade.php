{{-- resources/views/pages/db_inspector.blade.php --}}
@extends('layouts.app')

@section('title', 'DB Inspector')
@section('page-title', 'DB Inspector')

@push('styles')
<style>
  .table-fixed-header thead th { position: sticky; top: 0; background: #fff; z-index: 1; }
  .card-like { background:#fff;border:1px solid rgba(0,0,0,.06);border-radius:.75rem;box-shadow:0 10px 30px rgba(0,0,0,.03); }
  .small-muted { color:#6b7280; font-size:.9rem; }
  .mono { font-family: ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono","Courier New",monospace; }
</style>
@endpush

@section('content')
@php
    $conn   = DB::connection();
    $driver = $conn->getDriverName();

    $tables      = collect();
    $tableCounts = collect();
    $errorNote   = null;

    try {
        if ($driver === 'sqlite') {
            $tables = collect(DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'"))
                ->pluck('name')->sort()->values();
        } elseif ($driver === 'mysql') {
            $tables = collect(DB::select("
                SELECT table_name 
                FROM information_schema.tables 
                WHERE table_schema = DATABASE()
            "))->pluck('table_name')->sort()->values();
        } elseif ($driver === 'pgsql') {
            $tables = collect(DB::select("SELECT tablename FROM pg_tables WHERE schemaname = 'public'"))
                ->pluck('tablename')->sort()->values();
        } else {
            $errorNote = "Unsupported driver: {$driver}";
        }

        foreach ($tables as $t) {
            if ($t === 'migrations') continue;
            try {
                $tableCounts[$t] = DB::table($t)->count();
            } catch (\Throwable $e) {
                $tableCounts[$t] = '—';
            }
        }
    } catch (\Throwable $e) {
        $errorNote = $e->getMessage();
    }
@endphp

<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="mb-0">Database Inspector</h5>
  <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-sm">
    <i class="bi bi-arrow-left-short"></i> Back to Dashboard
  </a>
</div>

{{-- Connection summary --}}
<div class="card-like p-3 mb-3">
  <div class="row g-3 align-items-center">
    <div class="col-md-6">
      <div class="small-muted">Driver</div>
      <div class="fw-semibold text-capitalize">{{ $driver }}</div>
    </div>
    <div class="col-md-6">
      @if($driver === 'sqlite')
        @php $dbPath = $conn->getConfig('database'); @endphp
        <div class="small-muted">SQLite file</div>
        <div class="mono">{{ $dbPath ?: database_path('database.sqlite') }}</div>
      @endif
    </div>
  </div>
</div>

{{-- All tables — row counts --}}
<div class="card-like p-3 mb-3">
  <div class="d-flex justify-content-between align-items-center mb-2">
    <h6 class="mb-0">All Tables — Row Counts</h6>
    @if($errorNote)
      <span class="badge text-bg-warning">Note</span>
    @endif
  </div>
  @if($errorNote)
    <div class="small-muted mb-2">{{ $errorNote }}</div>
  @endif

  <div class="table-responsive">
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
            <td class="mono">{{ $name }}</td>
            <td class="text-end">{{ is_numeric($count) ? number_format($count) : $count }}</td>
          </tr>
        @empty
          <tr><td colspan="2" class="text-center text-secondary py-3">No tables found.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

{{-- Per-table data preview --}}
@if($tables->isNotEmpty())
  @foreach($tables as $tname)
    @php
        $limit = 200;
        $rows = collect();
        $columns = [];

        try {
            $rows = collect(DB::table($tname)->limit($limit)->get());
        } catch (\Throwable $e) {
            $rows = collect();
        }

        if ($rows->isNotEmpty()) {
            $columns = array_keys((array) $rows->first());
        } else {
            try {
                if ($driver === 'sqlite') {
                    $pragma = DB::select("PRAGMA table_info('$tname')");
                    $columns = collect($pragma)->pluck('name')->all();
                } elseif ($driver === 'mysql') {
                    $desc = DB::select("DESCRIBE `$tname`");
                    $columns = collect($desc)->pluck('Field')->all();
                } elseif ($driver === 'pgsql') {
                    $desc = DB::select("SELECT column_name FROM information_schema.columns WHERE table_name = ?", [$tname]);
                    $columns = collect($desc)->pluck('column_name')->all();
                }
            } catch (\Throwable $e) {
                $columns = [];
            }
        }

        $total = $tableCounts[$tname] ?? '—';
    @endphp

    <div class="card-like p-3 mb-3">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h6 class="mb-0 mono">{{ $tname }}</h6>
          <div class="small-muted">
            Showing {{ number_format(min(is_numeric($total) ? $total : 0, $limit)) }} of
            {{ is_numeric($total) ? number_format($total) : $total }} rows
          </div>
        </div>
        <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#t_{{ md5($tname) }}">
          Toggle Data
        </button>
      </div>

      <div id="t_{{ md5($tname) }}" class="collapse mt-3">
        <div class="table-responsive" style="max-height: 420px; overflow:auto;">
          <table class="table table-sm table-striped table-fixed-header mb-0">
            <thead class="table-light">
              <tr>
                @forelse($columns as $col)
                  <th class="mono">{{ $col }}</th>
                @empty
                  <th class="text-secondary">No columns detected</th>
                @endforelse
              </tr>
            </thead>
            <tbody>
              @forelse($rows as $r)
                @php $arr = (array) $r; @endphp
                <tr>
                  @foreach($columns as $col)
                    <td class="mono">
                      {{ isset($arr[$col]) ? (is_scalar($arr[$col]) ? (string)$arr[$col] : json_encode($arr[$col])) : '' }}
                    </td>
                  @endforeach
                </tr>
              @empty
                <tr>
                  <td colspan="{{ max(count($columns),1) }}" class="text-secondary text-center py-3">
                    No data in this table yet.
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  @endforeach
@endif
@endsection
