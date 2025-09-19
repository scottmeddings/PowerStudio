@extends('layouts.app')

@section('title','New House Campaign')
@section('page-title','house ads')

@section('content')
<div class="section-card p-3">
  <h5 class="mb-3">Create house campaign</h5>

  {{-- Flash success / error --}}
  @if(session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
  @endif
  @if($errors->any())
    <div class="alert alert-danger">
      <strong>There were some problems with your input.</strong>
      <ul class="mb-0 mt-2">
        @foreach($errors->all() as $err)
          <li>{{ $err }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <form method="POST" action="{{ route('monetization.house.store') }}" novalidate>
    @csrf
    <div class="row g-3">
      <div class="col-md-6">
        <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
        <input id="name" name="name" class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name') }}" required autocomplete="off" autofocus
               placeholder="e.g., Q4 Cross-Promo">
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      <div class="col-md-3">
        <label for="priority" class="form-label">Priority (1–10)</label>
        <input id="priority" name="priority" type="number" min="1" max="10"
               class="form-control @error('priority') is-invalid @enderror"
               value="{{ old('priority', 5) }}">
        @error('priority') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <div class="mini mt-1">Higher number wins when multiple promos fit.</div>
      </div>

      <div class="col-md-3">
        <label class="form-label d-flex align-items-center justify-content-between">
          <span>Dates</span>
          <span class="mini">Optional</span>
        </label>
        <div class="d-flex gap-2">
          <input id="start_at" name="start_at" type="date"
                 class="form-control @error('start_at') is-invalid @enderror"
                 value="{{ old('start_at') }}" placeholder="Start">
          <input id="end_at" name="end_at" type="date"
                 class="form-control @error('end_at') is-invalid @enderror"
                 value="{{ old('end_at') }}" placeholder="End">
        </div>
        @error('start_at') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        @error('end_at')   <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
      </div>
    </div>

    <div class="d-flex justify-content-between mt-4">
      <a href="{{ url()->previous() }}" class="btn btn-outline-secondary">Cancel</a>
      <button class="btn btn-dark">Create campaign</button>
    </div>
  </form>
</div>
@endsection
