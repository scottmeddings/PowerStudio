{{-- resources/views/site/templates/focuspod.blade.php --}}
@extends('layouts.app')
@section('title', $settings['title'])

@section('content')
<style>
  :root { --brand: {{ $settings['brand'] ?? '#7c3aed' }}; }
  .hero {
    border-radius: .75rem;
    background:
      linear-gradient(180deg, rgba(0,0,0,.35), rgba(0,0,0,.65)),
      {{ $settings['banner'] ? 'url('.asset('storage/'.$settings['banner']).')' : 'linear-gradient(135deg, var(--brand), #333)' }};
    background-size: cover;
    background-position: center;
    color:#fff;
  }
</style>

<section class="hero p-5 mb-4 shadow-sm">
  <div class="container-fluid px-0">
    <h1 class="display-6 fw-bold">{{ $settings['title'] }}</h1>
    <p class="lead mb-3">Latest episodes, highlights, and playlists.</p>
    @include('site.partials.subscribe-badges')
  </div>
</section>

<section>
  <div class="row g-3">
    @foreach($episodes as $ep)
      <div class="col-12 col-sm-6 col-xl-4">@include('site.partials.episode-card',['ep'=>$ep])</div>
    @endforeach
  </div>
  <div class="mt-4">{{ $episodes->links() }}</div>
</section>
@endsection
