{{-- resources/views/distribution/website.blade.php --}}
@extends('layouts.app')

@section('title','Podcast Website')
@section('page-title','Podcast Website · Themes & Settings')

@section('content')
@php
  // ---------- SAFE DEFAULTS ----------
  $settings = ($settings ?? []) + [
    'template' => 'zen',
    'title'    => config('app.name', 'PowerTime'),
    'brand'    => '#7c3aed',
    'font'     => "system-ui, -apple-system, Segoe UI, Roboto, Inter, Arial, sans-serif",
    'layout'   => 'list',
    'episodes_per_page' => 12,
    'show_subscribe_badges' => true,
    'banner'   => null,
  ];

  $templates = $templates ?? [
    ['slug'=>'zen','name'=>'Zen 1.0','by'=>'PodPower','img'=>asset('images/themes/zen.jpg'),
     'desc'=>'Large banner image, simple episode list, fast loads.'],
    ['slug'=>'frontrow','name'=>'Frontrow','by'=>'PodPower','img'=>asset('images/themes/frontrow.jpg'),
     'desc'=>'Profile card left, episodes right, great for bios.'],
    ['slug'=>'focuspod','name'=>'Focuspod','by'=>'PodPower','img'=>asset('images/themes/focuspod.jpg'),
     'desc'=>'Hero image with grid of episodes and tags.'],
  ];

  $current = $settings['template'] ?? 'zen';
@endphp

@if(session('ok'))
  <div class="alert alert-success">{{ session('ok') }}</div>
@endif
@if($errors->any())
  <div class="alert alert-danger">
    <strong>There were some problems:</strong>
    <ul class="mb-0">
      @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
    </ul>
  </div>
@endif

<style>
  :root{
    --card-bg:#fff; --card-border:#e9ecef;
    --fade-top:rgba(0,0,0,.05); --fade-bottom:rgba(0,0,0,.55);
  }
  .theme-card{
    border:1px solid var(--card-border); border-radius:16px; overflow:hidden;
    background:var(--card-bg); display:flex; flex-direction:column;
    transition:transform .18s ease, box-shadow .18s ease;
  }
  .theme-card:hover{ transform:translateY(-2px); box-shadow:0 8px 26px rgba(0,0,0,.08); }
  .theme-card.is-current{ border-color:#0ea5e9; }

  .theme-cover{ position:relative; aspect-ratio:16/9; background:#eef1f5 center/cover no-repeat; }
  .theme-cover__fade{ position:absolute; inset:0; background:linear-gradient(180deg, var(--fade-top) 0%, var(--fade-bottom) 85%); }
  .theme-cover__label{ position:absolute; left:12px; bottom:10px; background:rgba(255,255,255,.92);
    color:#111; font-size:.75rem; padding:.25rem .5rem; border-radius:.5rem; }

  .theme-ribbon{ position:absolute; right:-46px; top:14px; transform:rotate(45deg);
    background:#0ea5e9; color:#fff; font-weight:600; font-size:.75rem; padding:.35rem 2.2rem;
    box-shadow:0 6px 18px rgba(14,165,233,.35); }

  /* Modal layering guard without stacks */
  .modal{ z-index:1065 !important; }
  .modal-backdrop{ z-index:1060 !important; }
  .modal-backdrop.show{ opacity:.15; }
</style>

<div class="row g-4 align-items-stretch">
  @foreach($templates as $tpl)
    <div class="col-12 col-md-6 col-xl-4 d-flex">
      <div class="theme-card shadow-sm flex-fill {{ $current === $tpl['slug'] ? 'is-current' : '' }}">
        <div class="theme-cover" style="background-image:url('{{ $tpl['img'] }}')">
          <div class="theme-cover__fade"></div>
          <span class="theme-cover__label">{{ $tpl['name'] }} preview</span>
          @if($current === $tpl['slug'])
            <span class="theme-ribbon">Current</span>
          @endif
        </div>

        <div class="card-body p-3 p-md-4 d-flex flex-column">
          <div class="d-flex justify-content-between align-items-start mb-1">
            <div>
              <h5 class="card-title mb-1">{{ $tpl['name'] }}</h5>
              <small class="text-muted">by {{ $tpl['by'] }}</small>
            </div>
          </div>

          <p class="mt-3 mb-4 text-secondary small">{{ $tpl['desc'] }}</p>

          <div class="mt-auto d-flex gap-2">
            <button
              class="btn btn-outline-primary btn-sm px-3"
              type="button"
              data-bs-toggle="modal"
              data-bs-target="#settingsModal"
              data-template="{{ $tpl['slug'] }}">
              Customize
            </button>

            {{-- Open preview (no save) --}}
            <a href="{{ route('site.preview', ['template' => $tpl['slug']]) }}"
               class="btn btn-secondary btn-sm px-3 ms-auto"
               target="_blank" rel="noopener">
              Use Template
            </a>
          </div>
        </div>
      </div>
    </div>
  @endforeach
</div>

{{-- Modal inline (no stacks); we’ll reparent to <body> for safe z-index --}}
<div class="modal fade" id="settingsModal" tabindex="-1" aria-labelledby="settingsModalTitle" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
    <form class="modal-content" action="{{ route('website.themes.update') }}" method="post" enctype="multipart/form-data" novalidate>
      @csrf
      <div class="modal-header">
        <h5 class="modal-title" id="settingsModalTitle">Customize Website</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <input type="hidden" id="tpl-input" name="template" value="{{ $settings['template'] }}">

        <div class="row g-3">
          <div class="col-md-8">
            <label class="form-label">Site title</label>
            <input name="title" class="form-control" value="{{ old('title', $settings['title']) }}">
          </div>

          <div class="col-md-4">
            <label class="form-label">Brand color</label>
            <input name="brand" type="text" class="form-control" value="{{ old('brand', $settings['brand']) }}" placeholder="#7c3aed">
          </div>

          <div class="col-12">
            <label class="form-label">Font stack</label>
            <input name="font" class="form-control" value="{{ old('font', $settings['font']) }}">
            <small class="text-muted">Comma-separated CSS font-family.</small>
          </div>

          <div class="col-md-6">
            <label class="form-label d-block">Layout</label>
            <div class="btn-group" role="group">
              <input type="radio" class="btn-check" name="layout" id="layoutList" value="list" {{ old('layout', $settings['layout'])==='list'?'checked':'' }}>
              <label class="btn btn-outline-primary" for="layoutList">List</label>
              <input type="radio" class="btn-check" name="layout" id="layoutGrid" value="grid" {{ old('layout', $settings['layout'])==='grid'?'checked':'' }}>
              <label class="btn btn-outline-primary" for="layoutGrid">Grid</label>
            </div>
          </div>

          <div class="col-md-6">
            <label class="form-label">Episodes per page</label>
            <input type="number" class="form-control" name="episodes_per_page" min="6" max="48" value="{{ old('episodes_per_page', $settings['episodes_per_page']) }}">
          </div>

          <div class="col-12">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="badges" name="show_subscribe_badges" value="1" {{ old('show_subscribe_badges', $settings['show_subscribe_badges']) ? 'checked' : '' }}>
              <label class="form-check-label" for="badges">Show subscribe badges (Apple, Spotify, YouTube Music, etc.)</label>
            </div>
          </div>

          <div class="col-12">
            <label class="form-label">Banner image (optional)</label>
            <input type="file" class="form-control" name="banner" accept="image/*">
            @if(!empty($settings['banner']))
              <small class="text-muted d-block mt-2">Current: <code>{{ $settings['banner'] }}</code></small>
              <img class="img-fluid rounded mt-2" src="{{ asset('storage/'.$settings['banner']) }}" alt="Current banner">
              <div class="mt-2">
                <form action="{{ route('website.banner.clear') }}" method="post">
                  @csrf
                  <button class="btn btn-outline-danger btn-sm" type="submit">Remove banner</button>
                </form>
              </div>
            @endif
          </div>

          <div class="col-12">
            <hr>
            <small class="text-muted">
              Tip: You can switch templates anytime. Your title, brand color, and content persist.
            </small>
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Settings</button>
      </div>
    </form>
  </div>
</div>

<script>
  (function () {
    const modal = document.getElementById('settingsModal');
    if (!modal) return;

    // Ensure modal is a direct child of <body> (avoids z-index/transform traps)
    if (modal.parentElement && modal.parentElement.tagName !== 'BODY') {
      document.body.appendChild(modal);
    }

    // Inject template slug when opening Customize
    modal.addEventListener('show.bs.modal', function (event) {
      const btn = event.relatedTarget;
      const tpl = btn && btn.getAttribute('data-template');
      if (tpl) {
        const hidden = document.getElementById('tpl-input');
        if (hidden) hidden.value = tpl;
      }
    });
  }());
</script>
@endsection
