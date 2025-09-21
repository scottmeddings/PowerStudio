@extends('layouts.app')
@section('title','Distribution')
@section('page-title','Distribution · Podcast Apps')

@section('content')
@php
  // Safe fallbacks (your existing data will overwrite these)
  $directories = collect($directories ?? [
    ['slug'=>'apple','name'=>'Apple Podcasts','icon'=>'pi-apple','connected'=>false,'external_url'=>null],
    ['slug'=>'spotify','name'=>'Spotify','icon'=>'pi-spotify','connected'=>true ,'external_url'=>'https://open.spotify.com/show/...'],
    ['slug'=>'ytmusic','name'=>'YouTube Music','icon'=>'pi-ytm','connected'=>false,'external_url'=>null],
    ['slug'=>'amazon','name'=>'Amazon Music','icon'=>'pi-amazon','connected'=>false,'external_url'=>null],
    ['slug'=>'iheart','name'=>'iHeartRadio','icon'=>'pi-iheart','connected'=>false,'external_url'=>null],
    ['slug'=>'tunein','name'=>'TuneIn','icon'=>'pi-tunein','connected'=>false,'external_url'=>null],
    ['slug'=>'pocketcasts','name'=>'Pocket Casts','icon'=>'pi-pocket','connected'=>false,'external_url'=>null],
    ['slug'=>'overcast','name'=>'Overcast','icon'=>'pi-over','connected'=>false,'external_url'=>null],
    ['slug'=>'castbox','name'=>'Castbox','icon'=>'pi-castbx','connected'=>false,'external_url'=>null],
    ['slug'=>'deezer','name'=>'Deezer','icon'=>'pi-deezer','connected'=>false,'external_url'=>null],
    ['slug'=>'pandora','name'=>'Pandora','icon'=>'pi-pand','connected'=>false,'external_url'=>null],
  ]);

  $rss = $rss ?? url('/feed/podcast.xml');

  // Optional map from controller: ['spotify'=>true, ...]
  $connected = $connected ?? [];
  $is = function (string $slug) use ($connected, $directories) {
      $row = $directories->firstWhere('slug', $slug);
      return ($connected[$slug] ?? false) || (bool)($row['connected'] ?? false);
  };

  // Brand → Bootstrap Icons (fallback handled below)
  $brandIcons = [
    'apple'   => 'bi-apple',
    'spotify' => 'bi-spotify',
    'ytmusic' => 'bi-youtube',
    'amazon'  => 'bi-amazon',
    // the rest don’t exist in Bootstrap Icons — we’ll show the monogram fallback
  ];
@endphp

<style>
  .platform-card{
    background:#fff;border:1px solid rgba(0,0,0,.06);border-radius:.75rem;
    padding:1rem;display:flex;align-items:center;gap:1rem
  }
  .platform-chip{
    width:44px;height:44px;border-radius:12px;display:grid;place-items:center;color:#fff;
    font-weight:700;font-size:.95rem; flex:0 0 44px;
  }
  .platform-chip i.bi{ font-size:1.25rem; line-height:1; }

  /* brand background colors from your original classes */
  .pi-apple{background:#a970ff}.pi-spotify{background:#1db954}.pi-ytm{background:#ff0033}
  .pi-amazon{background:#00a8e1}.pi-iheart{background:#c6002b}.pi-tunein{background:#14a0a0}
  .pi-pocket{background:#f43f5e}.pi-over{background:#ff7a00}.pi-castbx{background:#f65e3b}
  .pi-deezer{background:#121216}.pi-pand{background:#224099}

  .platform-card .title{font-weight:600}
  .platform-card .meta{font-size:.85rem;color:#64748b}
  .platform-card .actions{margin-left:auto;display:flex;gap:.5rem}
  .badge-dot{width:8px;height:8px;border-radius:999px;display:inline-block;margin-right:.35rem}
  .bd-ok{background:#16a34a}.bd-wt{background:#9ca3af}
</style>

{{-- Directories grid --}}
<div class="row g-3">
  @foreach($directories as $dir)
    @php
      $slug = $dir['slug'];
      $connectedNow = $is($slug);
      $bgClass = $dir['icon'] ?? '';   // keeps your color classes (pi-apple etc.)
      $bi = $brandIcons[$slug] ?? null; // bootstrap icon class if we have it
      $monogram = strtoupper(mb_substr($dir['name'],0,1));
    @endphp

    <div class="col-12 col-md-6">
      <div class="platform-card">
        <div class="platform-chip {{ $bgClass }}" aria-hidden="true">
          @if($bi)
            <i class="bi {{ $bi }}"></i>
          @else
            {{ $monogram }}
          @endif
        </div>

        <div>
          <div class="title">{{ $dir['name'] }}</div>
          <div class="meta">
            <span class="badge-dot {{ $connectedNow ? 'bd-ok' : 'bd-wt' }}"></span>
            {{ $connectedNow ? 'Connected' : 'Not connected' }}
            @if($connectedNow && !empty($dir['external_url']))
              · <a href="{{ $dir['external_url'] }}" target="_blank" rel="noopener">View</a>
            @endif
          </div>
        </div>

        <div class="actions">
          <button
            class="btn btn-{{ $connectedNow ? 'outline-secondary' : 'dark' }} btn-sm"
            data-bs-toggle="modal"
            data-bs-target="#dirModal-{{ $slug }}">
            Connect
          </button>
        </div>
      </div>
    </div>
  @endforeach
</div>
@endsection

@push('modals')
  @foreach($directories as $dir)
    @php
      $slug = $dir['slug'];
      $connectedNow = $is($slug);
      $saveFormId = "saveForm-$slug";
      $disconnectFormId = "disconnectForm-$slug";
    @endphp

    <div class="modal fade" id="dirModal-{{ $slug }}" tabindex="-1" aria-labelledby="dirModalLabel-{{ $slug }}" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">

          <div class="modal-header">
            <h5 id="dirModalLabel-{{ $slug }}" class="modal-title">
              Connect — {{ $dir['name'] }}
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>

          {{-- SAVE form (uses form attribute on footer button) --}}
          <form id="{{ $saveFormId }}" method="POST" action="{{ route('distribution.save', $slug) }}">
            @csrf
            <input type="hidden" name="slug" value="{{ $slug }}">

            <div class="modal-body">
              <div class="mb-3">
                <label class="form-label">Directory URL</label>
                <input type="url"
                       name="external_url"
                       class="form-control"
                       placeholder="https://…"
                       value="{{ old('external_url', $dir['external_url'] ?? '') }}">
                <div class="form-text">
                  Paste the {{ $dir['name'] }} show URL. We’ll store it so you can manage it later.
                </div>
              </div>

              @if($connectedNow)
                <div class="alert alert-success py-2 mb-0">Currently connected.</div>
              @else
                <div class="alert alert-secondary py-2 mb-0">Not connected yet — add your show URL and Save.</div>
              @endif
            </div>
          </form>

          <div class="modal-footer">
            {{-- DISCONNECT form (separate, not nested) --}}
            @if($connectedNow)
              <form id="{{ $disconnectFormId }}" method="POST" action="{{ route('distribution.disconnect', $slug) }}" class="me-auto">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-outline-danger">Disconnect</button>
              </form>
            @endif

            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
            {{-- This submits the SAVE form above --}}
            <button type="submit" class="btn btn-dark" form="{{ $saveFormId }}">Save</button>
          </div>

        </div>
      </div>
    </div>
  @endforeach
@endpush

@push('scripts')
<script>
  document.getElementById('copyRssBtn')?.addEventListener('click', () => {
    const i = document.getElementById('rssInput');
    i?.select(); i?.setSelectionRange(0, 99999);
    if (navigator.clipboard) navigator.clipboard.writeText(i.value);
    else document.execCommand('copy');
  });
</script>
@endpush
