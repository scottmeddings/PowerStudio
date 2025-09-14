{{-- resources/views/site/episode.blade.php --}}
@extends('layouts.app')
@section('title', ($episode->title ?? 'Episode').' · '.$settings['title'])

@section('content')
<style>:root{--brand:{{ $settings['brand'] ?? '#7c3aed' }}}</style>

<div class="row g-4">
  <div class="col-lg-8">
    <h1 class="mb-2">{{ $episode->title ?? 'Untitled episode' }}</h1>
    @if($episode->published_at)
      <div class="text-muted mb-3">{{ \Illuminate\Support\Carbon::parse($episode->published_at)->format('M j, Y') }}</div>
    @endif

    {{-- Player --}}
    <div class="card shadow-sm mb-3">
      <div class="card-body">
        @php
          $audio = $episode->audio_url ?? ($episode->audio_path ? asset('storage/'.$episode->audio_path) : null);
        @endphp
        @if($audio)
          <audio controls style="width:100%">
            <source src="{{ $audio }}" type="audio/mpeg">
          </audio>
        @else
          <div class="alert alert-warning mb-0">Audio not available.</div>
        @endif
      </div>
    </div>

    {{-- Description --}}
    @if(!empty($episode->description))
      <div class="card shadow-sm">
        <div class="card-body">
          {!! $episode->description !!}
        </div>
      </div>
    @endif
  </div>

  <div class="col-lg-4">
    <div class="card shadow-sm">
      <div class="card-body">
        <h5 class="mb-3">Subscribe</h5>
        @include('site.partials.subscribe-badges')
      </div>
    </div>
  </div>
</div>
@endsection
