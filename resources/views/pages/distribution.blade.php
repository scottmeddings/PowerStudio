@extends('layouts.app')

@section('title', 'Distribution')
@section('page-title', 'distribution')

@section('content')
@php
  // Fallback RSS (replace with your real feed route/url if you have one)
  $rss = $rss ?? url('/feed/podcast.xml');

  // Optional: pass a $connected = ['spotify'=>true, ...] from the controller
  $connected = $connected ?? [];
  $is = fn($k) => ($connected[$k] ?? false);
@endphp

<style>
  .platform-card{
    background:#fff;border:1px solid rgba(0,0,0,.06);border-radius:.75rem;
    padding:1rem;display:flex;align-items:center;gap:1rem
  }
  .platform-icon{
    width:44px;height:44px;border-radius:12px;display:grid;place-items:center;color:#fff;
  }
  .pi-apple  { background:#a970ff; }     /* Apple Podcasts purple */
  .pi-spotify{ background:#1db954; }     /* Spotify green */
  .pi-ytm    { background:#ff0033; }     /* YouTube Music red */
  .pi-amazon { background:#00a8e1; }     /* Amazon Music cyan */
  .pi-iheart { background:#c6002b; }
  .pi-tunein { background:#14a0a0; }
  .pi-pocket { background:#f43f5e; }
  .pi-over   { background:#ff7a00; }
  .pi-castbx { background:#f65e3b; }
  .pi-deezer { background:#121216; }
  .pi-pand   { background:#224099; }

  .platform-card .title { font-weight:600; }
  .platform-card .meta  { font-size:.85rem; color:#64748b; }
  .platform-card .actions{ margin-left:auto; display:flex; gap:.5rem; }
  .badge-dot{ width:8px;height:8px;border-radius:999px;display:inline-block;margin-right:.35rem }
  .bd-ok { background:#16a34a; }
  .bd-wt { background:#9ca3af; }
</style>

{{-- RSS feed card --}}
<div class="section-card p-3 mb-3">
  <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-2">
    <div class="me-lg-3">
      <div class="fw-semibold">Your RSS feed</div>
      <small class="text-secondary">Submit this URL to any directory.</small>
    </div>
    <div class="input-group">
      <input id="rssInput" type="text" class="form-control" value="{{ $rss }}" readonly>
      <button class="btn btn-outline-secondary" type="button" id="copyRssBtn">
        <i class="bi bi-clipboard-check"></i> Copy
      </button>
    </div>
  </div>
</div>

{{-- Directories grid --}}
<div class="row g-3">

  {{-- Apple Podcasts --}}
  <div class="col-12 col-md-6">
    <div class="platform-card">
      <div class="platform-icon pi-apple">
        {{-- Apple Podcasts (Simple Icons) --}}
        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
          <path d="M12 0a12 12 0 1 0 12 12A12.013 12.013 0 0 0 12 0Zm0 18.941a.88.88 0 0 1-.88-.881v-1.97a.88.88 0 0 1 1.76 0v1.97a.88.88 0 0 1-.88.881Zm3.938-1.292a.879.879 0 0 1-1.239-.183 4.764 4.764 0 0 0-1.112-1.108 3.49 3.49 0 0 0-4.175 0 4.764 4.764 0 0 0-1.112 1.108.879.879 0 0 1-1.42-1.056 6.52 6.52 0 0 1 1.478-1.47 5.25 5.25 0 0 1 6.323 0 6.52 6.52 0 0 1 1.478 1.47.879.879 0 0 1-.221 1.239Zm1.963-3.426a.879.879 0 0 1-1.177-.41 8.73 8.73 0 0 0-7.488-4.529 8.73 8.73 0 0 0-7.488 4.529.879.879 0 1 1-1.586-.768A10.492 10.492 0 0 1 7.92 7.17a10.49 10.49 0 0 1 8.158 0 10.492 10.492 0 0 1 4.76 5.874.879.879 0 0 1- .937.179Z"/>
        </svg>
      </div>
      <div>
        <div class="title">Apple Podcasts</div>
        <div class="meta">
          <span class="badge-dot {{ $is('apple') ? 'bd-ok' : 'bd-wt' }}"></span>
          {{ $is('apple') ? 'Connected' : 'Not connected' }}
        </div>
      </div>
      <div class="actions">
        @if($is('apple'))
          <a class="btn btn-outline-secondary btn-sm" href="#">Manage</a>
        @else
          <a class="btn btn-dark btn-sm" href="#">Submit</a>
        @endif
      </div>
    </div>
  </div>

  {{-- Spotify --}}
  <div class="col-12 col-md-6">
    <div class="platform-card">
      <div class="platform-icon pi-spotify">
        {{-- Spotify --}}
        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
          <path d="M12 .5C5.65.5.5 5.65.5 12S5.65 23.5 12 23.5 23.5 18.35 23.5 12 18.35.5 12 .5Zm5.39 16.96a.77.77 0 0 1-1.06.27c-2.89-1.77-6.53-2.17-10.81-1.19a.77.77 0 1 1-.34-1.5c4.7-1.07 8.73-.62 11.93 1.32.37.23.49.72.28 1.1Zm1.47-3.28a.96.96 0 0 1-1.32.34c-3.31-2-8.35-2.59-12.26-1.42a.96.96 0 1 1-.55-1.85c4.39-1.31 9.92-.66 13.66 1.58.45.27.6.86.32 1.35Zm.13-3.46a1.13 1.13 0 0 1-1.57.4c-3.78-2.27-10.11-2.48-13.74-1.38A1.13 1.13 0 0 1 2.7 8.12c4.2-1.26 11.12-1 15.38 1.55.54.32.72 1.02.41 1.55Z"/>
        </svg>
      </div>
      <div>
        <div class="title">Spotify</div>
        <div class="meta">
          <span class="badge-dot {{ $is('spotify') ? 'bd-ok' : 'bd-wt' }}"></span>
          {{ $is('spotify') ? 'Connected' : 'Not connected' }}
        </div>
      </div>
      <div class="actions">
        @if($is('spotify'))
          <a class="btn btn-outline-secondary btn-sm" href="#">Manage</a>
        @else
          <a class="btn btn-dark btn-sm" href="#">Submit</a>
        @endif
      </div>
    </div>
  </div>

  {{-- YouTube Music (Google Podcasts migration) --}}
  <div class="col-12 col-md-6">
    <div class="platform-card">
      <div class="platform-icon pi-ytm">
        {{-- YouTube Music --}}
        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
          <path d="M12 1.5a10.5 10.5 0 1 0 10.5 10.5A10.512 10.512 0 0 0 12 1.5Zm0 18.9a8.4 8.4 0 1 1 8.4-8.4 8.41 8.41 0 0 1-8.4 8.4Zm0-14.1a5.7 5.7 0 1 0 5.7 5.7A5.707 5.707 0 0 0 12 6.3Zm-1.8 3.6 5.1 3-5.1 3Z"/>
        </svg>
      </div>
      <div>
        <div class="title">YouTube Music</div>
        <div class="meta">
          <span class="badge-dot {{ $is('ytmusic') ? 'bd-ok' : 'bd-wt' }}"></span>
          {{ $is('ytmusic') ? 'Connected' : 'Not connected' }}
        </div>
      </div>
      <div class="actions">
        @if($is('ytmusic'))
          <a class="btn btn-outline-secondary btn-sm" href="#">Manage</a>
        @else
          <a class="btn btn-dark btn-sm" href="#">Submit</a>
        @endif
      </div>
    </div>
  </div>

  {{-- Amazon Music --}}
  <div class="col-12 col-md-6">
    <div class="platform-card">
      <div class="platform-icon pi-amazon">
        {{-- Amazon Music (note: stylized play) --}}
        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
          <path d="M3 6.5a1 1 0 0 1 1.52-.85l9.26 5.35a1 1 0 0 1 0 1.7L4.52 18.05A1 1 0 0 1 3 17.2Z"/>
        </svg>
      </div>
      <div>
        <div class="title">Amazon Music</div>
        <div class="meta">
          <span class="badge-dot {{ $is('amazon') ? 'bd-ok' : 'bd-wt' }}"></span>
          {{ $is('amazon') ? 'Connected' : 'Not connected' }}
        </div>
      </div>
      <div class="actions">
        @if($is('amazon'))
          <a class="btn btn-outline-secondary btn-sm" href="#">Manage</a>
        @else
          <a class="btn btn-dark btn-sm" href="#">Submit</a>
        @endif
      </div>
    </div>
  </div>

  {{-- iHeartRadio --}}
  <div class="col-12 col-md-6">
    <div class="platform-card">
      <div class="platform-icon pi-iheart">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M12 21s-7.2-4.66-9.5-7.58A5.58 5.58 0 1 1 12 6.56a5.58 5.58 0 1 1 9.5 6.86C19.2 16.34 12 21 12 21Z"/></svg>
      </div>
      <div><div class="title">iHeartRadio</div>
        <div class="meta"><span class="badge-dot {{ $is('iheart')?'bd-ok':'bd-wt' }}"></span>{{ $is('iheart')?'Connected':'Not connected' }}</div>
      </div>
      <div class="actions">
        <a class="btn btn-{{ $is('iheart')?'outline-secondary':'dark' }} btn-sm" href="#">{{ $is('iheart')?'Manage':'Submit' }}</a>
      </div>
    </div>
  </div>

  {{-- TuneIn --}}
  <div class="col-12 col-md-6">
    <div class="platform-card">
      <div class="platform-icon pi-tunein">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M4 7h16v10H4zM7 9h7v6H7zM16 9h2v6h-2z"/></svg>
      </div>
      <div><div class="title">TuneIn</div>
        <div class="meta"><span class="badge-dot {{ $is('tunein')?'bd-ok':'bd-wt' }}"></span>{{ $is('tunein')?'Connected':'Not connected' }}</div>
      </div>
      <div class="actions">
        <a class="btn btn-{{ $is('tunein')?'outline-secondary':'dark' }} btn-sm" href="#">{{ $is('tunein')?'Manage':'Submit' }}</a>
      </div>
    </div>
  </div>

  {{-- Pocket Casts --}}
  <div class="col-12 col-md-6">
    <div class="platform-card">
      <div class="platform-icon pi-pocket">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 1 0 10 10h-2A8 8 0 1 1 12 4Z"/><path d="M12 6a6 6 0 0 0-6 6h2a4 4 0 1 1 4 4v2a6 6 0 0 0 0-12Z"/></svg>
      </div>
      <div><div class="title">Pocket Casts</div>
        <div class="meta"><span class="badge-dot {{ $is('pocketcasts')?'bd-ok':'bd-wt' }}"></span>{{ $is('pocketcasts')?'Connected':'Not connected' }}</div>
      </div>
      <div class="actions">
        <a class="btn btn-{{ $is('pocketcasts')?'outline-secondary':'dark' }} btn-sm" href="#">{{ $is('pocketcasts')?'Manage':'Submit' }}</a>
      </div>
    </div>
  </div>

  {{-- Overcast --}}
  <div class="col-12 col-md-6">
    <div class="platform-card">
      <div class="platform-icon pi-over">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2Zm3.5 15.5L12 13l-3.5 4.5-1.5-1L10 12 7 7.5l1.5-1L12 11l3.5-4.5 1.5 1L14 12l3 4.5Z"/></svg>
      </div>
      <div><div class="title">Overcast</div>
        <div class="meta"><span class="badge-dot {{ $is('overcast')?'bd-ok':'bd-wt' }}"></span>{{ $is('overcast')?'Connected':'Not connected' }}</div>
      </div>
      <div class="actions">
        <a class="btn btn-{{ $is('overcast')?'outline-secondary':'dark' }} btn-sm" href="#">{{ $is('overcast')?'Manage':'Submit' }}</a>
      </div>
    </div>
  </div>

  {{-- Castbox --}}
  <div class="col-12 col-md-6">
    <div class="platform-card">
      <div class="platform-icon pi-castbx">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="m12 2 8 4.62v9.76L12 21l-8-4.62V6.62L12 2Zm0 2.31L6 6.77v6.46L12 16.9l6-3.67V6.77L12 4.31Z"/></svg>
      </div>
      <div><div class="title">Castbox</div>
        <div class="meta"><span class="badge-dot {{ $is('castbox')?'bd-ok':'bd-wt' }}"></span>{{ $is('castbox')?'Connected':'Not connected' }}</div>
      </div>
      <div class="actions">
        <a class="btn btn-{{ $is('castbox')?'outline-secondary':'dark' }} btn-sm" href="#">{{ $is('castbox')?'Manage':'Submit' }}</a>
      </div>
    </div>
  </div>

  {{-- Deezer --}}
  <div class="col-12 col-md-6">
    <div class="platform-card">
      <div class="platform-icon pi-deezer">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="#ffdd00"><rect x="3" y="14" width="3" height="4"/><rect x="7" y="10" width="3" height="8" fill="#ff9900"/><rect x="11" y="12" width="3" height="6" fill="#ff0066"/><rect x="15" y="8" width="3" height="10" fill="#00ccff"/><rect x="19" y="6" width="3" height="12" fill="#66ff66"/></svg>
      </div>
      <div><div class="title">Deezer</div>
        <div class="meta"><span class="badge-dot {{ $is('deezer')?'bd-ok':'bd-wt' }}"></span>{{ $is('deezer')?'Connected':'Not connected' }}</div>
      </div>
      <div class="actions">
        <a class="btn btn-{{ $is('deezer')?'outline-secondary':'dark' }} btn-sm" href="#">{{ $is('deezer')?'Manage':'Submit' }}</a>
      </div>
    </div>
  </div>

  {{-- Pandora --}}
  <div class="col-12 col-md-6">
    <div class="platform-card">
      <div class="platform-icon pi-pand">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M6 3h7a8 8 0 1 1 0 16H9v2H6z"/></svg>
      </div>
      <div><div class="title">Pandora</div>
        <div class="meta"><span class="badge-dot {{ $is('pandora')?'bd-ok':'bd-wt' }}"></span>{{ $is('pandora')?'Connected':'Not connected' }}</div>
      </div>
      <div class="actions">
        <a class="btn btn-{{ $is('pandora')?'outline-secondary':'dark' }} btn-sm" href="#">{{ $is('pandora')?'Manage':'Submit' }}</a>
      </div>
    </div>
  </div>

</div> {{-- /row --}}
@endsection

@push('scripts')
<script>
  document.getElementById('copyRssBtn')?.addEventListener('click', () => {
    const i = document.getElementById('rssInput');
    i.select(); i.setSelectionRange(0, 99999);
    const ok = navigator.clipboard ? navigator.clipboard.writeText(i.value) : document.execCommand('copy');
  });
</script>
@endpush
