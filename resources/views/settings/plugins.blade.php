@extends('layouts.app')

@section('title','Settings · Plugins')
@section('page-title','Plugins')

@section('content')
  <div class="section-card p-4">
    @if(session('ok')) <div class="alert alert-success">{{ session('ok') }}</div> @endif

    <form method="POST" action="{{ route('settings.plugins.update') }}">
      @csrf

      <p class="text-muted">Take your podcast to the next level by adding popular plugins and services.</p>

      @php
        $available = [
          'transcripts' => 'Transcripts Generator',
          'chapters'    => 'Chapters & Timestamps',
          'audiograms'  => 'Audiogram Creator',
          'auto-share'  => 'Auto-share to Social',
        ];
        $enabled = collect($enabled_plugins ?? []);
      @endphp

      <div class="row g-3">
        @foreach($available as $code => $label)
          <div class="col-md-6">
            <div class="form-check border rounded p-3 h-100">
              <input class="form-check-input" type="checkbox" value="{{ $code }}" id="plg_{{ $code }}"
                     name="plugins[]" @checked($enabled->contains($code))>
              <label class="form-check-label fw-semibold" for="plg_{{ $code }}">{{ $label }}</label>
              <div class="small text-muted">Enable {{ strtolower($label) }} for new episodes.</div>
            </div>
          </div>
        @endforeach
      </div>

      <div class="mt-4">
        <button class="btn btn-blush">Update Options</button>
      </div>
    </form>
  </div>
@endsection
