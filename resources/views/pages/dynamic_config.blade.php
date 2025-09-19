@extends('layouts.app')
@section('title','Dynamic Ad Insertion')
@section('page-title','dynamic ads')
@section('content')
<div class="section-card p-3">
  <h5 class="mb-3">Dynamic Ad Settings</h5>
  <form method="POST" action="{{ route('monetization.dynamic.save') }}">@csrf
    <div class="row g-3">
      <div class="col-sm-4">
        <label class="form-label">Status</label>
        <select name="status" class="form-select">
          @foreach(['disabled','selling','paused'] as $s)
            <option value="{{ $s }}" @selected(($cfg->status ?? 'disabled')===$s)>{{ ucfirst($s) }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-sm-4">
        <label class="form-label">Default fill (%)</label>
        <input class="form-control" type="number" min="0" max="100" name="default_fill" value="{{ $cfg->default_fill ?? 70 }}">
      </div>
      <div class="col-sm-4">
        <label class="form-label">Slots (pre / mid / post)</label>
        <div class="d-flex gap-2">
          <input class="form-control" type="number" min="0" max="5"   name="pre_total"  value="{{ $cfg->pre_total ?? 1 }}">
          <input class="form-control" type="number" min="0" max="10"  name="mid_total"  value="{{ $cfg->mid_total ?? 2 }}">
          <input class="form-control" type="number" min="0" max="5"   name="post_total" value="{{ $cfg->post_total ?? 1 }}">
        </div>
      </div>
    </div>
    <div class="text-end mt-3">
      <button class="btn btn-dark">Save</button>
    </div>
  </form>
</div>
@endsection
