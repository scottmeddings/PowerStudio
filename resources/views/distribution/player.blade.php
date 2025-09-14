@extends('layouts.app')

@section('title','Distribution')
@section('page-title','Distribution · Podcast Apps')

@section('content')

{{-- ---------- SAFE DEFAULTS & EMBED CODE BUILDER ---------- --}}
@php
  // --- SAFE DEFAULTS (must be before embed-code builder) ---
  $theme   = ($theme   ?? request('theme', 'light')) ?: 'light';
  $brand   = ($brand   ?? request('color', '#7c3aed')) ?: '#7c3aed';
  if ($brand[0] !== '#') $brand = '#'.$brand;
  $font    = $font    ?? "system-ui, -apple-system, Segoe UI, Roboto, Inter, Arial, sans-serif";
  $height  = (int)($height  ?? (int)request('height', 315));
  $share   = $share   ?? filter_var(request('share', true),    FILTER_VALIDATE_BOOLEAN);
  $download= $download?? filter_var(request('download', true), FILTER_VALIDATE_BOOLEAN);
  $showcode= $showcode?? filter_var(request('showcode', true), FILTER_VALIDATE_BOOLEAN);
  $preselectSlug = $preselectSlug ?? request('episode');

  // BEFORE use below
  $limitParam = (int)($limitParam ?? request('limit', 10));
  $orderParam = (string)($orderParam ?? request('order', 'newest')); // newest|oldest

  $site = $site ?? [
      'title' => config('app.name', 'PowerTime'),
      'link'  => rtrim(config('app.url'), '/'),
  ];
  /** @var \Illuminate\Support\Collection $episodes */
  $episodes = $episodes ?? collect();

  // theme colors
  $bg    = $theme === 'dark' ? '#0b0f17' : ($theme === 'auto' ? 'transparent' : '#ffffff');
  $fg    = $theme === 'dark' ? '#e5e7eb' : '#111827';
  $muted = $theme === 'dark' ? '#9ca3af' : '#6b7280';

  // --- EMBED CODE BUILDER ---
  $query = http_build_query(array_filter([
      'limit'    => $limitParam ?: null,
      'order'    => $orderParam ?: 'newest',
      'theme'    => $theme,
      'color'    => ltrim($brand, '#'),
      'share'    => $share ? 1 : 0,
      'download' => $download ? 1 : 0,
      'episode'  => $preselectSlug ?: null,
  ]));

  $iframeSrc = route('embed.player') . ($query ? ('?'.$query) : '');
  $scriptSrc = route('embed.player.script');

  $episodeAttr = $preselectSlug ? "\n     data-episode=\"{$preselectSlug}\"" : '';
  $shareStr    = $share ? 'true' : 'false';
  $downloadStr = $download ? 'true' : 'false';

  $embedA = <<<HTML
<iframe
  title="PowerTime"
  src="{$iframeSrc}"
  width="100%" height="{$height}"
  style="border:0;overflow:hidden"
  allow="autoplay"
  loading="lazy"></iframe>
HTML;

  $embedB = <<<HTML
<div data-powertime-player
     data-limit="{$limitParam}"
     data-order="{$orderParam}"
     data-theme="{$theme}"
     data-color="{$brand}"
     data-height="{$height}"
     data-share="{$shareStr}"
     data-download="{$downloadStr}"{$episodeAttr}></div>
<script async src="{$scriptSrc}"></script>
HTML;

  $first = $episodes->first();
  $headerCover = $first?->cover_url ?? asset('images/podcast-cover.jpg');
@endphp

<div class="container py-3">
  <div class="card p-3" style="background: {{ $bg }}; color: {{ $fg }};">
    <div class="d-flex align-items-center gap-3">
      {{-- fixed extra quote on placeholder --}}
      <img id="cover" src="{{ 'https://placehold.co/480x480?text=Cover' }}"
           class="rounded" style="width:84px;height:84px;object-fit:cover" alt="">
      <div class="flex-grow-1">
        <div class="fw-semibold small text-uppercase" style="letter-spacing:.06em">{{ $site['title'] }}</div>
        <div id="title" class="h6 mb-1">{{ $episodes->first()->title ?? 'Episode' }}</div>
        <div class="d-flex gap-2">
          <button id="play" class="btn btn-brand btn-sm px-3">Play</button>
          @if($download)
            <a id="download" href="#" class="btn btn-outline-secondary btn-sm">Download</a>
          @endif
          @if($share)
            <a id="share" href="#" class="btn btn-outline-secondary btn-sm">Share</a>
          @endif
        </div>
      </div>
      <div class="text-end">
        <div class="time"><span id="cur">0:00</span> / <span id="dur">0:00</span></div>
      </div>
    </div>

    {{-- Plyr audio (built-in progress bar) --}}
    <audio id="audioPlayer" class="w-100 mt-3" preload="metadata" crossorigin="anonymous" controls></audio>

    <div class="mt-2 list-group list-group-flush" id="list">
      @foreach($episodes as $e)
        <button class="list-group-item episode d-flex align-items-center gap-3 p-2"
                data-audio="{{ $e->playable_url }}"
                data-title="{{ $e->title }}"
                {{-- fixed extra quote on placeholder --}}
                data-image="{{ 'https://placehold.co/480x480?text=Cover' }}"
                @if($preselectSlug === $e->slug) data-preselect="1" @endif>
          <img class="thumb" src="{{ 'https://placehold.co/480x480?text=Cover' }}" alt="">
          <div class="text-start">
            <div class="fw-semibold">{{ $e->title }}</div>
            <div class="small text-muted">
              {{ optional($e->published_at)->format('M j, Y') }}
              @if($e->duration_sec) · {{ gmdate('i:s', (int)$e->duration_sec) }} @endif
            </div>
          </div>
        </button>
      @endforeach
    </div>
  </div>

  {{-- ===== Customizer panel ===== --}}
  <div class="card p-3 mt-3">
    <div class="fw-semibold mb-2">Customize</div>

    <div class="row g-3">
      <div class="col-6 col-md-3">
        <label class="form-label small">Player color</label>
        <input id="ctl-color" type="color" class="form-control form-control-color" value="{{ $brand }}">
      </div>

      <div class="col-6 col-md-3">
        <label class="form-label small">Display order</label>
        <select id="ctl-order" class="form-select">
          <option value="newest" {{ $orderParam==='newest'?'selected':'' }}>Episodic (New → Old)</option>
          <option value="oldest" {{ $orderParam==='oldest'?'selected':'' }}>Episodic (Old → New)</option>
        </select>
      </div>

      <div class="col-6 col-md-3">
        <label class="form-label small">Number of episodes</label>
        <input id="ctl-limit" type="number" min="1" max="100" class="form-control" value="{{ $limitParam }}">
      </div>

      <div class="col-6 col-md-3">
        <label class="form-label small">Theme</label>
        <select id="ctl-theme" class="form-select">
          <option value="light" {{ $theme==='light'?'selected':'' }}>Light</option>
          <option value="dark"  {{ $theme==='dark'?'selected':'' }}>Dark</option>
          <option value="auto"  {{ $theme==='auto'?'selected':'' }}>Auto</option>
        </select>
      </div>

      <div class="col-6 col-md-3">
        <label class="form-label small">Font</label>
        <select id="ctl-font" class="form-select">
          <option value="system-ui, -apple-system, Segoe UI, Roboto, Inter, Arial, sans-serif" selected>System / Inter</option>
          <option value="Arial, sans-serif">Arial</option>
          <option value="Georgia, serif">Georgia</option>
          <option value="ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Inter, Arial, sans-serif">Sans Serif</option>
        </select>
      </div>

      <div class="col-6 col-md-3">
        <label class="form-label small">Height (iframe)</label>
        <input id="ctl-height" type="number" min="200" step="5" class="form-control" value="{{ $height }}">
      </div>

      <div class="col-6 col-md-3">
        <label class="form-label small">Share</label>
        <select id="ctl-share" class="form-select">
          <option value="1" {{ $share?'selected':'' }}>Show</option>
          <option value="0" {{ !$share?'selected':'' }}>Hide</option>
        </select>
      </div>

      <div class="col-6 col-md-3">
        <label class="form-label small">Download</label>
        <select id="ctl-download" class="form-select">
          <option value="1" {{ $download?'selected':'' }}>Show</option>
          <option value="0" {{ !$download?'selected':'' }}>Hide</option>
        </select>
      </div>
    </div>
  </div>
  {{-- ===== /Customizer panel ===== --}}

  @if($showcode)
    <div class="mt-3">
      <div class="card p-3">
        <div class="d-flex align-items-center justify-content-between mb-2">
          <div class="fw-semibold">Widget Code</div>
          <div class="small text-muted">Copy and paste into any site</div>
        </div>

        <ul class="nav nav-pills mb-3" id="embedTabs" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active" id="optA-tab" data-bs-toggle="pill" data-bs-target="#optA" type="button" role="tab">
              Option A — iframe
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="optB-tab" data-bs-toggle="pill" data-bs-target="#optB" type="button" role="tab">
              Option B — script
            </button>
          </li>
        </ul>

        <div class="tab-content">
          <div class="tab-pane fade show active" id="optA" role="tabpanel">
            <div class="position-relative">
              <textarea id="codeA" class="form-control" rows="6" readonly
                style="font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono','Courier New', monospace;">{!! $embedA !!}</textarea>
              <button class="btn btn-sm btn-brand position-absolute" style="top:10px; right:10px" data-copy="#codeA">Copy</button>
            </div>
          </div>
          <div class="tab-pane fade" id="optB" role="tabpanel">
            <div class="position-relative">
              <textarea id="codeB" class="form-control" rows="10" readonly
                style="font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono','Courier New', monospace;">{!! $embedB !!}</textarea>
              <button class="btn btn-sm btn-brand position-absolute" style="top:10px; right:10px" data-copy="#codeB">Copy</button>
            </div>
          </div>
        </div>

      </div>
    </div>
  @endif
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/plyr@3.7.8/dist/plyr.css">
<style>
  :root{
    --brand: {{ $brand }};
    --bg: {{ $bg }};
    --fg: {{ $fg }};
    --fg-muted: {{ $muted }};
    --radius: 14px;
    --plyr-color-main: var(--brand); /* brand the Plyr progress bar */
  }
  .btn-brand{ background:var(--brand); border-color:var(--brand); color:#fff; }
  .btn-brand:hover{ filter:brightness(0.95);}
  .card{ border-radius:var(--radius); border:1px solid rgba(0,0,0,.06); background:inherit; color: var(--fg); }
  .episode{ cursor:pointer }
  .episode.active{ outline:2px solid var(--brand); border-radius:10px; }
  .thumb{ width:56px; height:56px; border-radius:10px; object-fit:cover; }
  .time{ color:var(--fg-muted); font-variant-numeric: tabular-nums; }
  a, .link { color: var(--brand) }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/plyr@3.7.8/dist/plyr.polyfilled.min.js"></script>
<script>
(function(){
  const $  = (s,root=document)=>root.querySelector(s);
  const $$ = (s,root=document)=>[...root.querySelectorAll(s)];

  const playBtn = $('#play'),
        cur     = $('#cur'),
        dur     = $('#dur'),
        titleEl = $('#title'),
        cover   = $('#cover'),
        list    = $('#list'),
        dl      = $('#download'),
        share   = $('#share');

  // Init Plyr on the <audio>
  const player = new Plyr('#audioPlayer', {
    controls: ['play','progress','current-time','duration','mute','volume'],
    invertTime: false
  });

  function fmt(s){ s=Math.max(0, s|0); const m=(s/60)|0, r=(s%60)|0; return m+':'+String(r).padStart(2,'0'); }

  let currentBtn = null;

  function load(btn){
    if(!btn) return;
    $$('.episode', list).forEach(b=>b.classList.toggle('active', b===btn));
    currentBtn = btn;

    const src   = btn.dataset.audio;
    const name  = btn.dataset.title;
    const image = btn.dataset.image;

    titleEl.textContent = name;
    if (image) cover.src = image;
    if (dl) dl.href = src;

    // set source (no autoplay)
    player.source = { type: 'audio', sources: [{ src, type: 'audio/mpeg' }] };
    playBtn.textContent = 'Play';
  }

  // Top Play button toggles Plyr
  playBtn.addEventListener('click', ()=>{
    if (!player.source || !player.source?.sources?.length) {
      load($('.episode', list)); // pick first
    }
    if (player.playing) player.pause(); else player.play();
  });

  // Sync top button + times
  player.on('playing', ()=> playBtn.textContent='Pause');
  player.on('pause',   ()=> playBtn.textContent='Play');
  player.on('timeupdate', ()=>{
    cur.textContent = fmt(player.currentTime || 0);
    dur.textContent = Number.isFinite(player.duration) ? fmt(player.duration) : '--:--';
  });
  player.on('loadedmetadata', ()=>{
    if (Number.isFinite(player.duration)) dur.textContent = fmt(player.duration);
  });

  // List item click loads track (no autoplay)
  list.addEventListener('click', (e)=>{
    const btn = e.target.closest('.episode'); if(btn) load(btn);
  });

  // Preselect first (no autoplay)
  const pre = $('.episode[data-preselect="1"]', list) || $('.episode', list);
  if (pre) load(pre);

  // Share
  if (share){
    share.addEventListener('click', (e)=>{
      e.preventDefault();
      const url = (currentBtn && currentBtn.dataset.audio) || window.location.href;
      if (navigator.share) navigator.share({ title: titleEl.textContent, url });
      else {
        navigator.clipboard.writeText(url);
        share.textContent = 'Copied!'; setTimeout(()=>share.textContent='Share', 1200);
      }
    });
  }

  // Copy buttons (for embed code boxes)
  document.querySelectorAll('[data-copy]').forEach(btn=>{
    btn.addEventListener('click', async ()=>{
      const sel = btn.getAttribute('data-copy');
      const ta = document.querySelector(sel);
      if (!ta) return;
      await navigator.clipboard.writeText(ta.value || ta.textContent || '');
      const old = btn.textContent; btn.textContent='Copied!'; setTimeout(()=>btn.textContent=old,1200);
    });
  });
})();
</script>

{{-- Customizer logic: live preview + regenerate embed code --}}
<script>
(function(){
  const $ = s => document.querySelector(s);
  const routeIframe = @json(route('embed.player'));
  const routeScript = @json(route('embed.player.script'));

  // Inputs
  const inp = {
    color:   $('#ctl-color'),
    order:   $('#ctl-order'),
    limit:   $('#ctl-limit'),
    theme:   $('#ctl-theme'),
    font:    $('#ctl-font'),
    height:  $('#ctl-height'),
    share:   $('#ctl-share'),
    download:$('#ctl-download'),
  };

  // Elements to update live
  const shareBtn = $('#share');
  const dlBtn    = $('#download');
  const list     = $('#list');

  // Code boxes (if present)
  const codeA = $('#codeA');
  const codeB = $('#codeB');

  function applyThemeVars(theme){
    let bg, fg, muted;
    if (theme === 'dark'){ bg='#0b0f17'; fg='#e5e7eb'; muted='#9ca3af'; }
    else if (theme === 'auto'){ bg='transparent'; fg='#111827'; muted='#6b7280'; }
    else { bg='#ffffff'; fg='#111827'; muted='#6b7280'; }
    document.documentElement.style.setProperty('--bg', bg);
    document.documentElement.style.setProperty('--fg', fg);
    document.documentElement.style.setProperty('--fg-muted', muted);
  }

  function applyLiveStyles(){
    // brand color + plyr brand + font
    document.documentElement.style.setProperty('--brand', inp.color.value);
    document.documentElement.style.setProperty('--plyr-color-main', inp.color.value);
    document.documentElement.style.setProperty('font-family', inp.font.value);

    // theme colors
    applyThemeVars(inp.theme.value);

    // show/hide buttons
    if (shareBtn) shareBtn.parentElement.style.display = inp.share.value === '1' ? '' : 'none';
    if (dlBtn)    dlBtn.parentElement.style.display    = inp.download.value === '1' ? '' : 'none';

    // order + limit: tweak preview list client-side
    const items = Array.from(list.querySelectorAll('.episode'));
    // restore DOM order first
    items.forEach(b => list.appendChild(b));
    if (inp.order.value === 'oldest') items.reverse().forEach(b => list.appendChild(b));
    items.forEach((b,i)=> b.style.display = i < Number(inp.limit.value||10) ? '' : 'none');
  }

  function buildQuery(){
    const p = new URLSearchParams();
    p.set('limit',    inp.limit.value || '10');
    p.set('order',    inp.order.value);
    p.set('theme',    inp.theme.value);
    p.set('color',    (inp.color.value || '#7c3aed').replace('#',''));
    p.set('font',     inp.font.value);
    p.set('share',    inp.share.value);
    p.set('download', inp.download.value);
    return p.toString();
  }

  function updateEmbeds(){
  const q = buildQuery();
  const iframe = `<iframe
  title="PowerTime"
  src="${routeIframe}?${q}"
  width="100%" height="${inp.height.value || 315}"
  style="border:0;overflow:hidden"
  allow="autoplay"
  loading="lazy"></iframe>`;

  // ⬇️ ESCAPE the closing script tag
  const script = `<div data-powertime-player
     data-limit="${inp.limit.value || 10}"
     data-order="${inp.order.value}"
     data-theme="${inp.theme.value}"
     data-color="${inp.color.value}"
     data-height="${inp.height.value || 315}"
     data-share="${inp.share.value === '1'}"
     data-download="${inp.download.value === '1'}"></div>
<script async src="${routeScript}"><\\/script>`;

  if (codeA) codeA.value = iframe;
  if (codeB) codeB.value = script;
}

  function onChange(){ applyLiveStyles(); updateEmbeds(); }
  Object.values(inp).forEach(el => el && el.addEventListener('input', onChange));
  Object.values(inp).forEach(el => el && el.addEventListener('change', onChange));

  // initial
  onChange();
})();
</script>
@endpush
