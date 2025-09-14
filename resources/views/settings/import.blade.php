@extends('layouts.app')

@section('title','Settings · Import to Podpower')
@section('page-title','Import to Podpower')

@section('content')
  <div class="section-card p-4">
    @if(session('ok')) <div class="alert alert-success">{{ session('ok') }}</div> @endif

    <form method="POST" action="{{ route('settings.import.handle') }}" class="row g-4">
      @csrf

      <div class="col-12">
        <h5 class="mb-3">Step 1: Enter Your RSS Feed URL</h5>
        <label class="form-label fw-semibold">RSS Feed *</label>
        <input name="import_feed_url" class="form-control @error('import_feed_url') is-invalid @enderror"
               value="{{ old('import_feed_url',$import_feed_url) }}" placeholder="https://example.com/feed.xml" required>
        @error('import_feed_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      <div class="col-12">
        <h5 class="mb-2">Step 2: Import Options</h5>
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="do_301" id="do_301" value="1"
                 @checked(old('do_301', cache('settings:do_301', false)))>
          <label class="form-check-label" for="do_301">Set 301 redirect after import</label>
        </div>
      </div>

      <div class="col-12">
        <button class="btn btn-blush">Next</button>
      </div>
    </form>
  </div>
@endsection
