{{-- resources/views/pages/facebook_select_page.blade.php --}}
@extends('layouts.app')

@section('title','Select Facebook Page')
@section('page-title','Select Facebook Page')

@section('content')
  @if(session('err'))
    <div class="alert alert-danger">{{ session('err') }}</div>
  @endif

  <div class="section-card p-4">
    <h5 class="mb-3">Choose the Page to publish to</h5>

    @if(empty($pages))
      <div class="text-muted">No pages returned. Make sure you granted access to your Pages.</div>
    @else
      <form method="POST" action="{{ route('social.facebook.select_page.save') }}">
        @csrf
        <div class="list-group mb-3">
          @foreach ($pages as $p)
            <label class="list-group-item d-flex align-items-center gap-2">
              <input class="form-check-input me-2" type="radio" name="page_id" value="{{ $p['id'] }}" required>
              <div>
                <div class="fw-semibold">{{ $p['name'] ?? 'Page' }}</div>
                <div class="small text-muted">ID: {{ $p['id'] }}</div>
              </div>
            </label>
          @endforeach
        </div>
        <button class="btn btn-dark">Use this Page</button>
      </form>
    @endif
  </div>
@endsection
