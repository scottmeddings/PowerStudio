{{-- resources/views/distribution/website.blade.php --}}
@extends('layouts.app')

@section('title','Podcast Website')
@section('page-title','Podcast Website · Themes & Settings')

@section('content')
@php
  use Illuminate\Support\Str;
  use Illuminate\Support\Facades\Storage;
  use Illuminate\Support\Facades\Route as RouteFacade;

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
    'site_slug' => $settings['site_slug'] ?? null,
  ];

  $templates = $templates ?? [
    ['slug'=>'zen','name'=>'Zen 1.0','by'=>'PodPower','img'=>asset('images/powertime-hero.png'),'desc'=>'Large banner image, simple episode list, fast loads.'],
    ['slug'=>'frontrow','name'=>'Frontrow','by'=>'PodPower','img'=>asset('images/powertime-hero.png'),'desc'=>'Profile card left, episodes right, great for bios.'],
    ['slug'=>'focuspod','name'=>'Focuspod','by'=>'PodPower','img'=>asset('images/powertime-hero.png'),'desc'=>'Hero image with grid of episodes and tags.'],
  ];

  $current = $settings['template'] ?? 'zen';

// ---------- PUBLIC LINK (user-scoped & unique) ----------
$uid = auth()->id() ?? 'guest';
$computedSlug = $settings['site_slug']
  ?? Str::of(auth()->user()->name ?? ('user-'.$uid))->slug('-')->append('-')->append(substr(md5($uid), 0, 6));
$publicBase   = url('/site/u/'.$uid);

// build a computed URL as fallback
$computedUrl  = $publicBase.'/'.(($settings['template'] ?? 'zen')).'/'.$computedSlug;

// ✅ prefer DB value when present, otherwise fallback
$publicUrl = $settings['public_url'] ?? null;



  // ---------- RESOLVE BANNER URL ----------
  $resolvedBannerUrl = null;
  if (isset($bannerUrl) && $bannerUrl) {
    $resolvedBannerUrl = $bannerUrl;
  } else {
    $rawBanner = session('uploaded_banner') ?: ($settings['banner'] ?? null);
    if (!empty($rawBanner)) {
      $norm = ltrim(preg_replace('#^public/#i', '', (string)$rawBanner), "/\\");
      $norm = str_replace('\\','/',$norm);
      $norm = preg_replace('#/+#','/',$norm);
      $resolvedBannerUrl = Str::startsWith($norm, ['http://','https://']) ? $norm : Storage::disk('public')->url($norm);
    }
  }

  // ---------- ENDPOINTS ----------
  $updateUrl = route('website.themes.update'); // existing form route
  $quickUrl  = RouteFacade::has('website.themes.quick') ? route('website.themes.quick') : null; // optional JSON route
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
  :root{ --card-bg:#fff; --card-border:#e9ecef; --fade-top:rgba(0,0,0,.05); --fade-bottom:rgba(0,0,0,.55); }
  .theme-card{ border:1px solid var(--card-border); border-radius:16px; overflow:hidden; background:var(--card-bg); display:flex; flex-direction:column; transition:transform .18s ease, box-shadow .18s ease; }
  .theme-card:hover{ transform:translateY(-2px); box-shadow:0 8px 26px rgba(0,0,0,.08); }
  .theme-card.is-current{ border-color:#0ea5e9; }
  .theme-cover{ position:relative; aspect-ratio:16/9; background:#eef1f5 center/cover no-repeat; }
  .theme-cover__fade{ position:absolute; inset:0; background:linear-gradient(180deg, var(--fade-top) 0%, var(--fade-bottom) 85%); }
  .theme-cover__label{ position:absolute; left:12px; bottom:10px; background:rgba(255,255,255,.92); color:#111; font-size:.75rem; padding:.25rem .5rem; border-radius:.5rem; }
  .theme-ribbon{ position:absolute; right:-46px; top:14px; transform:rotate(45deg); background:#0ea5e9; color:#fff; font-weight:600; font-size:.75rem; padding:.35rem 2.2rem; box-shadow:0 6px 18px rgba(14,165,233,.35); }
  .modal{ z-index:1065 !important; }
  .modal-backdrop{ z-index:1060 !important; }
  .modal-backdrop.show{ opacity:.15; }
  .copy-btn{ white-space:nowrap; }
</style>

{{-- ======= PUBLIC LINK CARD (TOP) ======= --}}
<div class="card shadow-sm mb-4">
  <div class="card-body">
    <label for="publicLink" class="form-label mb-2">
      Public website link
    </label>
    <div class="input-group">
      <input id="publicLink" type="text" class="form-control" value="{{ $publicUrl }}" readonly>
      <a id="openPublicLink" href="{{ $publicUrl }}" target="_blank" rel="noopener" class="btn btn-outline-secondary">Open</a>
      <button id="copyPublicLink" type="button" class="btn btn-outline-primary copy-btn">
        <i class="bi bi-clipboard"></i> Copy
      </button>
    </div>
  </div>
</div>

<div class="row g-4 align-items-stretch">
  @foreach($templates as $tpl)
    @php
      $previewImg = $resolvedBannerUrl ?: $tpl['img'];
      $tplUrl     = $publicBase.'/'.$tpl['slug'].'/'.$computedSlug;
      $isCurrent  = $current === $tpl['slug'];
    @endphp
    <div class="col-12 col-md-6 col-xl-4 d-flex">
      <div class="theme-card shadow-sm flex-fill {{ $isCurrent ? 'is-current' : '' }}" data-template="{{ $tpl['slug'] }}">
        <div class="theme-cover" style="background-image:url('{{ $previewImg }}')">
          <div class="theme-cover__fade"></div>
          <span class="theme-cover__label">{{ $tpl['name'] }} preview</span>
          @if($isCurrent)
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
            <button class="btn btn-outline-primary btn-sm px-3" type="button"
                    data-bs-toggle="modal" data-bs-target="#settingsModal"
                    data-template="{{ $tpl['slug'] }}">
              Customize
            </button>

            <a href="{{ $tplUrl }}" target="_blank" rel="noopener"
               class="btn btn-outline-secondary btn-sm px-3">
              Preview
            </a>

            <button type="button"
                    class="btn btn-secondary btn-sm px-3 ms-auto js-use-template"
                    data-template="{{ $tpl['slug'] }}"
                    data-url="{{ $tplUrl }}"
                    {{ $isCurrent ? 'disabled aria-disabled=true' : '' }}>
              {{ $isCurrent ? 'Current' : 'Use Template' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  @endforeach
</div>

{{-- Modal --}}
<div class="modal fade" id="settingsModal" tabindex="-1" aria-labelledby="settingsModalTitle" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
    <form class="modal-content" action="{{ $updateUrl }}" method="post" enctype="multipart/form-data" novalidate>
      @csrf
      <input type="hidden" id="tpl-input" name="template" value="{{ $settings['template'] }}">
      {{-- ensure slug persists on full form save as well --}}
      <input type="hidden" name="site_slug" value="{{ $computedSlug }}">
      <input type="hidden" id="clear-banner-input" name="clear_banner" value="0">

      <div class="modal-header">
        <h5 class="modal-title" id="settingsModalTitle">Customize Website</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
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
            @if(!empty($settings['banner']) || session('uploaded_banner'))
              <small class="text-muted d-block mt-2">Current: <code>{{ session('uploaded_banner') ?: ($settings['banner'] ?? '') }}</code></small>
              @if($resolvedBannerUrl)
                <img class="img-fluid rounded mt-2" src="{{ $resolvedBannerUrl }}" alt="Current banner">
              @endif
              <div class="mt-2">
                <button type="button" id="remove-banner-btn" class="btn btn-outline-danger btn-sm">Remove banner</button>
              </div>
            @endif
          </div>
          <div class="col-12">
            <hr>
            <small class="text-muted">Tip: You can switch templates anytime. Your title, brand color, and content persist.</small>
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
  if (modal && modal.parentElement && modal.parentElement.tagName !== 'BODY') {
    document.body.appendChild(modal);
  }

  // Inject template slug when opening Customize
  modal?.addEventListener('show.bs.modal', function (event) {
    const btn = event.relatedTarget;
    const tpl = btn && btn.getAttribute('data-template');
    if (tpl) document.getElementById('tpl-input').value = tpl;
  });

  // Remove banner without nested forms (set hidden field)
  const removeBtn = document.getElementById('remove-banner-btn');
  removeBtn?.addEventListener('click', function () {
    const clearInput = document.getElementById('clear-banner-input');
    if (clearInput) clearInput.value = '1';
    const fileInput = modal.querySelector('input[type="file"][name="banner"]');
    if (fileInput) fileInput.value = '';
  });

  // Copy public link
  const copyBtn = document.getElementById('copyPublicLink');
  const linkInput = document.getElementById('publicLink');
  copyBtn?.addEventListener('click', async function () {
    try { linkInput.select(); linkInput.setSelectionRange(0, 99999);
      await navigator.clipboard.writeText(linkInput.value);
      copyBtn.innerHTML = '<i class="bi bi-clipboard-check"></i> Copied';
      setTimeout(() => copyBtn.innerHTML = '<i class="bi bi-clipboard"></i> Copy', 1500);
    } catch (e) { document.execCommand('copy'); }
  });

  // ===== Helpers =====
  const q  = (s, c) => (c||document).querySelector(s);
  const qa = (s, c) => Array.from((c||document).querySelectorAll(s));

  function getCsrf() {
    const m = document.querySelector('meta[name="csrf-token"]');
    if (m?.content) return m.content;
    const i = document.querySelector('input[name="_token"]');
    return i?.value || '';
  }

  // Endpoint URLs (Blade-injected)
  const ENDPOINT_QUICK  = @json($quickUrl);   // may be null
  const ENDPOINT_UPDATE = @json($updateUrl);  // exists

  // ===== Use Template: persist to DB, then mark blue Current =====
  document.querySelectorAll('.js-use-template').forEach(btn => {
    btn.addEventListener('click', async () => {
      const tpl  = btn.getAttribute('data-template');
      const url  = btn.getAttribute('data-url'); // fallback if backend doesn’t return JSON
      const card = btn.closest('.theme-card');
      const csrf = getCsrf();

      // Ignore if already current (button is disabled in markup too)
      if (btn.disabled) return;

      const original = btn.innerHTML;
      btn.disabled = true; btn.innerHTML = 'Saving…';

      try {
        const endpoint = ENDPOINT_QUICK || ENDPOINT_UPDATE;
        const res = await fetch(endpoint, {
          method: 'POST',
          credentials: 'same-origin',
          redirect: 'follow',
          headers: {
            'X-CSRF-TOKEN': csrf,
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
            'Accept': ENDPOINT_QUICK ? 'application/json' : 'text/html'
          },
          body: new URLSearchParams({
            template: tpl,
            site_slug: @json($computedSlug),
          }),
        });

        if (res.status === 419) throw new Error('Session expired (419). Refresh and try again.');
        if (res.status === 401 || res.status === 403) throw new Error('Not authorized—please sign in again.');
        if (res.status >= 400) throw new Error('Save failed (' + res.status + ').');

        // Prefer canonical URL from JSON quick endpoint
        let liveUrl = url;
        if (ENDPOINT_QUICK) {
          try {
            const json = await res.json();
            if (json?.public_url) liveUrl = json.public_url;
          } catch { /* fall back */ }
        }

        // Update UI: top URL and blue "Current" ribbon
        document.querySelectorAll('.theme-card').forEach(c => {
          c.classList.remove('is-current');
          c.querySelector('.theme-ribbon')?.remove();
          const useBtn = c.querySelector('.js-use-template');
          if (useBtn) { useBtn.disabled = false; useBtn.textContent = 'Use Template'; }
        });

        if (card) {
          card.classList.add('is-current');
          const cover = card.querySelector('.theme-cover') || card;
          const r = document.createElement('span');
          r.className = 'theme-ribbon'; r.textContent = 'Current';
          cover.appendChild(r);
          // disable this card's Use button since it's current now
          btn.disabled = true; btn.textContent = 'Current';
        }

        if (liveUrl) {
          const input = document.getElementById('publicLink');
          const open  = document.getElementById('openPublicLink');
          if (input) input.value = liveUrl;
          if (open)  open.setAttribute('href', liveUrl);
        }

        // keep hidden template in sync for modal Save
        const hiddenTpl = document.getElementById('tpl-input');
        if (hiddenTpl) hiddenTpl.value = tpl;

      } catch (e) {
        console.error(e);
        alert(e.message || 'Sorry—could not save this template. Please try again.');
        btn.innerHTML = original; btn.disabled = false;
      }
    });
  });
})();
</script>
@endsection
