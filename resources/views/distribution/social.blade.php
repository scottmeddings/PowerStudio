{{-- resources/views/distribution/social.blade.php --}}
@extends('layouts.app')

@section('title','Distribution · Social Share')
@section('page-title','Social Share')

@section('content')
<style>
  .brand-chip {
    width: 42px; height: 42px;
    display:inline-grid; place-items:center;
    border-radius: 12px; color:#fff;
  }
  .brand-chip i { font-size: 1.25rem; line-height: 1; }
</style>

@php
  // icon = Bootstrap Icons class; bg = brand color
  $brands = [
    'facebook'  => ['label' => 'Facebook',  'icon' => 'bi-facebook',  'bg' => '#1877F2'],
    'linkedin'  => ['label' => 'LinkedIn',  'icon' => 'bi-linkedin',  'bg' => '#0A66C2'],
    'youtube'   => ['label' => 'YouTube',   'icon' => 'bi-youtube',   'bg' => '#FF0033'],
    'tumblr'    => ['label' => 'Tumblr',    'icon' => 'bi-tumblr',    'bg' => '#35465C'],
    'wordpress' => ['label' => 'WordPress', 'icon' => 'bi-wordpress', 'bg' => '#21759B'],
  ];

  // If controller passed $providers and $connections
  $providers   = $providers   ?? array_keys($brands);
  $connections = $connections ?? collect();
@endphp

<div class="section-card p-4">
  <p class="text-muted mb-4">
    Connect to share your newly published episodes to social accounts automatically.
  </p>

  @foreach ($providers as $p)
    @php
      $meta = $brands[$p] ?? ['label' => ucfirst($p), 'icon' => 'bi-plug', 'bg' => '#6b7280'];
      $conn = $connections[$p] ?? null;
      $isConnected = (bool) ($conn->is_connected ?? false);
    @endphp

    <div class="d-flex align-items-center justify-content-between border rounded bg-white p-3 mb-3">
      <div class="d-flex align-items-center gap-3">
        <span class="brand-chip" style="background: {{ $meta['bg'] }}">
          <i class="bi {{ $meta['icon'] }}"></i>
        </span>
        <div>
          <div class="fw-semibold">{{ $meta['label'] }}</div>
          <div class="small {{ $isConnected ? 'text-success' : 'text-muted' }}">
            {{ $isConnected ? 'Connected' : 'Not connected' }}
          </div>
        </div>
      </div>

      <div class="d-flex align-items-center gap-2">
        @if($isConnected)
          <form method="POST" action="{{ route('distribution.social.disconnect', $p) }}">
            @csrf @method('DELETE')
            <button class="btn btn-outline-danger btn-sm">Disconnect</button>
          </form>
          <form method="POST" action="{{ route('distribution.social.test', $p) }}">
            @csrf
            <button class="btn btn-outline-secondary btn-sm">Send test</button>
          </form>
        @else
          <a class="btn btn-primary btn-sm" href="{{ route('distribution.social.auth', $p) }}">
            <i class="bi bi-plug me-1"></i>Connect
          </a>
        @endif
      </div>
    </div>
  @endforeach
</div>
@endsection
