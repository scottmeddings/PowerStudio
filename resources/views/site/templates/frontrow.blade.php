{{-- resources/views/site/templates/frontrow.blade.php --}}
@extends('layouts.app')
@section('title', $settings['title'])

@section('content')
<style>:root { --brand: {{ $settings['brand'] ?? '#7c3aed' }}; }</style>

<div class="row g-4">
  <aside class="col-lg-4">
    <div class="card shadow-sm sticky-top" style="top:1rem">
      <div class="card-body">
        <div class="d-flex align-items-center mb-3">
          <div class="rounded-circle bg-gradient p-1 me-3" style="background:linear-gradient(135deg,var(--brand),#111)">
            <img src="{{ asset('images/podcast-cover.jpg') }}" class="rounded-circle" width="64" height="64" alt="Cover">
          </div>
          <div>
            <h5 class="mb-0">{{ $settings['title'] }}</h5>
            <small class="text-muted">Hosted by {{ config('app.name','PowerTime') }}</small>
          </div>
        </div>
        <p class="text-secondary">Exploring Microsoft AI, low-code, and tech leadership.</p>
        @include('site.partials.subscribe-badges')
      </div>
    </div>
  </aside>

  <main class="col-lg-8">
    @if($settings['layout']==='grid')
      <div class="row g-3">
        @foreach($episodes as $ep)
          <div class="col-12 col-md-6">@include('site.partials.episode-card',['ep'=>$ep])</div>
        @endforeach
      </div>
    @else
      @foreach($episodes as $ep)
        @php
          $img = $ep->cover_image ? (Str::startsWith($ep->cover_image, ['http://','https://']) ? $ep->cover_image : asset('storage/'.$ep->cover_image)) : asset('images/episode-fallback.jpg');
          $date = $ep->published_at ? \Illuminate\Support\Carbon::parse($ep->published_at)->format('M j, Y') : null;
          $desc = \Illuminate\Support\Str::limit(strip_tags($ep->description ?? ''), 220);
        @endphp
        <div class="card shadow-sm mb-3">
          <div class="row g-0">
            <div class="col-md-4">
              <img class="img-fluid rounded-start h-100 object-fit-cover" src="{{ $img }}" alt="">
            </div>
            <div class="col-md-8">
              <div class="card-body">
                <h5 class="card-title"><a class="text-decoration-none" href="{{ route('site.episode', $ep->slug) }}">{{ $ep->title }}</a></h5>
                @if($date)<div class="text-muted small mb-2">{{ $date }}</div>@endif
                @if($desc)<p class="mb-2">{{ $desc }}</p>@endif
                <a href="{{ route('site.episode', $ep->slug) }}" class="btn btn-sm btn-primary">Play</a>
              </div>
            </div>
          </div>
        </div>
      @endforeach
    @endif

    <div class="mt-4">{{ $episodes->links() }}</div>
  </main>
</div>
@endsection
