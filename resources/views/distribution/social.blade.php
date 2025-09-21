{{-- resources/views/pages/distribution_social.blade.php --}}
@extends('layouts.app')

@section('title','Distribution · Social Share')
@section('page-title','Social Share')

@section('content')
@php
  // --- Providers rendered in UI ---
  $socials = collect([
    ['slug'=>'x',        'name'=>'X (Twitter)', 'icon'=>'bi-twitter-x', 'bg'=>'#111111'],
    ['slug'=>'linkedin', 'name'=>'LinkedIn',    'icon'=>'bi-linkedin',  'bg'=>'#0A66C2'],
    ['slug'=>'facebook', 'name'=>'Facebook',    'icon'=>'bi-facebook',  'bg'=>'#1877F2'],
    ['slug'=>'instagram','name'=>'Instagram',   'icon'=>'bi-instagram', 'bg'=>'#C13584'],
    ['slug'=>'threads',  'name'=>'Threads',     'icon'=>'bi-threads',   'bg'=>'#000000'],
    ['slug'=>'youtube',  'name'=>'YouTube',     'icon'=>'bi-youtube',   'bg'=>'#FF0033'],
    ['slug'=>'tiktok',   'name'=>'TikTok',      'icon'=>'bi-tiktok',    'bg'=>'#000000'],
  ]);

  // --- Normalise ANY incoming $socialConnected into a lookup set ---
  $raw = collect($socialConnected ?? []);
  $connectedSet = $raw
    ->map(function ($v, $k) {
      if (is_string($k) && is_bool($v)) return $v ? $k : null;          // ['linkedin'=>true]
      if (is_string($v)) return $v;                                      // ['linkedin','x']
      if (is_array($v))  return $v['provider'] ?? $v['slug'] ?? null;    // rows
      if (is_object($v)) return $v->provider ?? $v->slug ?? null;        // models
      return null;
    })
    ->filter(fn($s) => is_string($s) && $s !== '')
    ->unique()
    ->values()
    ->flip(); // keys = slugs; has($slug) => connected

  // --- Self-heal from DB if nothing was passed in ---
  if ($connectedSet->isEmpty() && auth()->check()) {
    $connectedSet = auth()->user()->socialAccounts()->pluck('provider')->unique()->flip();
  }

  $directories = collect($directories ?? []);
@endphp

@if(session('ok') || session('err'))
  <div class="alert {{ session('ok') ? 'alert-success' : 'alert-danger' }} mb-3">
    {{ session('ok') ?? session('err') }}
  </div>
@endif

<style>
  .brand-chip{ width:42px;height:42px;display:inline-grid;place-items:center;border-radius:12px;color:#fff }
  .brand-chip i{ font-size:1.25rem;line-height:1 }
  .platform-card{ background:var(--c-card-bg); border:1px solid var(--c-card-border); border-radius:.75rem; padding:1rem; display:flex; align-items:center; gap:1rem }
  .badge-dot{ width:8px;height:8px;border-radius:999px;display:inline-block;margin-right:.35rem }
  .bd-ok{ background:#16a34a } .bd-wt{ background:#9ca3af }
  .muted{ color:var(--c-muted) }
  .composer-card{ border:1px solid var(--c-card-border); border-radius:.75rem; background:var(--c-card-bg); }
  .composer-toolbar .btn{ --bs-btn-padding-y:.35rem; --bs-btn-padding-x:.55rem }
  .editor-surface{ min-height:260px; padding:10px 12px; border-radius:.5rem; border:1px solid var(--c-card-border); background:#fff; }
  html[data-theme="dark"] .editor-surface{ background:#0f1524; }
  .editor-placeholder:empty:before{ content: attr(data-placeholder); color: var(--c-muted); }
</style>

<div class="d-flex align-items-center justify-content-between mb-3">
  <div class="muted">Connect your social accounts for one-click sharing.</div>
  <button class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#newSocialPostModal">
    <i class="bi bi-plus-lg me-1"></i> New Post
  </button>
</div>

<div class="section-card p-4">
  <p class="muted mb-4">
    Connect accounts to publish to multiple networks at once. Credentials use encrypted storage. You can switch to full OAuth later.
  </p>

  <div class="row g-3">
    @foreach ($socials as $sp)
      @php
        $slug = $sp['slug'];
        $isConnected = $connectedSet->has($slug);
      @endphp

      <div class="col-12 col-md-6">
        <div class="platform-card">
          <span class="brand-chip" style="background:{{ $sp['bg'] }}"><i class="bi {{ $sp['icon'] }}"></i></span>

          <div>
            <div class="fw-semibold">{{ $sp['name'] }}</div>
            <div class="small {{ $isConnected ? '' : 'muted' }}">
              <span class="badge-dot {{ $isConnected ? 'bd-ok' : 'bd-wt' }}"></span>
              {{ $isConnected ? 'Connected' : 'Not connected' }}
            </div>
          </div>

          <div class="ms-auto d-flex align-items-center gap-2">
            @if ($isConnected)
              <form method="POST" action="{{ route('social.disconnect', ['provider'=>$slug]) }}">
                @csrf @method('DELETE')
                <button class="btn btn-outline-danger btn-sm">
                  <i class="bi bi-x-circle me-1"></i> Disconnect
                </button>
              </form>
            @else
              @if ($slug === 'linkedin')
                <a class="btn btn-dark btn-sm" href="{{ route('social.linkedin.redirect') }}">
                  <i class="bi bi-plug me-1"></i> Connect
                </a>
              @else
                <button class="btn btn-dark btn-sm" data-bs-toggle="modal" data-bs-target="#socialModal-{{ $slug }}">
                  <i class="bi bi-plug me-1"></i> Connect
                </button>
              @endif
            @endif
          </div>
        </div>
      </div>
    @endforeach
  </div>
</div>
@endsection

@push('modals')
  {{-- Provider modals (skip LinkedIn, uses real OAuth) --}}
  @foreach ($socials as $sp)
    @php $slug = $sp['slug']; @endphp
    @if ($slug === 'linkedin') @continue @endif
    <div class="modal fade" id="socialModal-{{ $slug }}" tabindex="-1" aria-labelledby="socialLabel-{{ $slug }}" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content composer-card">
          <div class="modal-header border-0 pb-0">
            <h5 id="socialLabel-{{ $slug }}" class="modal-title d-flex align-items-center gap-2">
              <span class="brand-chip" style="background:{{ $sp['bg'] }}"><i class="bi {{ $sp['icon'] }}"></i></span>
              Connect — {{ $sp['name'] }}
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form method="POST" action="{{ route('social.oauth.start', ['provider'=>$slug]) }}">
            @csrf
            <div class="modal-body pt-0">
              <div class="mb-3">
                <label class="form-label">Username or Email</label>
                <input type="text" name="username" class="form-control" required>
              </div>
              <div class="mb-2">
                <label class="form-label d-flex justify-content-between">
                  <span>Password</span>
                  <a href="#" class="small">Forgot?</a>
                </label>
                <input type="password" name="password" class="form-control" required>
              </div>
              <div class="form-text">You’ll be authorized by {{ $sp['name'] }}. We do not store your password.</div>
            </div>
            <div class="modal-footer border-0">
              <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-dark">Continue</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  @endforeach

  {{-- FULL New Post Composer --}}
  <div class="modal fade" id="newSocialPostModal" tabindex="-1" aria-labelledby="newSocialPostLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
      <div class="modal-content composer-card">

        <div class="modal-header border-0">
          <h5 id="newSocialPostLabel" class="modal-title">Create a post</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <form id="socialPostForm" method="POST" action="{{ route('distribution.social.post') }}" enctype="multipart/form-data">
          @csrf

          @php
            $dirsPayload = ($directories ?? collect())
              ->map(fn($d) => [
                  'slug'         => $d['slug']         ?? null,
                  'name'         => $d['name']         ?? null,
                  'connected'    => (bool)($d['connected'] ?? false),
                  'external_url' => $d['external_url'] ?? null,
              ])
              ->values()
              ->toJson(JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT);
          @endphp
          <input type="hidden" id="connectedDirectoriesJson" value="{{ $dirsPayload }}">
          <textarea name="body" id="bodyField" class="d-none" aria-hidden="true"></textarea>

          <div class="modal-body pt-0">
            {{-- Header --}}
            <div class="d-flex align-items-center gap-3 mb-3">
              @php $user = auth()->user(); @endphp
              @if($user?->avatar_url)
                <img src="{{ $user->avatar_url }}" alt="Avatar" class="rounded-circle object-fit-cover" style="width:42px;height:42px;">
              @else
                <span class="brand-chip" style="background:linear-gradient(135deg,#6366f1,#06b6d4)"><i class="bi bi-person"></i></span>
              @endif
              <div>
                <div class="fw-semibold">{{ $user?->name ?? 'You' }}</div>
                <div>
                  <select class="form-select form-select-sm w-auto d-inline-block" name="visibility">
                    <option value="public" selected>Anyone</option>
                    <option value="connections">Connections</option>
                    <option value="private">Only me (draft)</option>
                  </select>
                </div>
              </div>
            </div>

            {{-- Service picker (respect connections) --}}
            <div class="mb-3">
              <label class="form-label">Choose services</label>
              <div class="d-flex flex-wrap gap-2" id="servicesPicker">
                @foreach ($socials as $sp)
                  @php
                    $slug = $sp['slug'];
                    $connected = $connectedSet->has($slug);
                  @endphp
                  <input type="checkbox"
                         class="btn-check"
                         name="services[]"
                         value="{{ $slug }}"
                         id="svc-{{ $slug }}"
                         autocomplete="off"
                         @checked($connected)
                         {{ $connected ? '' : 'disabled' }}>
                  <label class="btn {{ $connected ? 'btn-outline-secondary' : 'btn-outline-secondary disabled' }}"
                         for="svc-{{ $slug }}"
                         title="{{ $connected ? 'Connected' : 'Connect this service first' }}">
                    <i class="bi {{ $sp['icon'] }} me-1"></i>{{ $sp['name'] }}
                  </label>
                @endforeach
              </div>
              <div class="form-text">Only connected services can be selected.</div>
            </div>

            {{-- Title + URL --}}
            <div class="row g-3">
              <div class="col-md-8">
                <label class="form-label">Title (internal)</label>
                <input type="text" name="title" class="form-control" placeholder="">
              </div>
              <div class="col-md-4">
                <label class="form-label">Include links</label>
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="includeConnected" checked>
                  <label class="form-check-label" for="includeConnected">All connected podcast apps</label>
                </div>
              </div>
              <div class="col-12">
                <label class="form-label">Episode / Landing URL (optional)</label>
                <input type="url" name="episode_url" class="form-control" placeholder="">
              </div>
            </div>

            {{-- Editor --}}
            <div class="mt-3">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="btn-group composer-toolbar" role="group" aria-label="Formatting">
                  <button type="button" class="btn btn-outline-secondary" data-cmd="bold" title="Bold"><i class="bi bi-type-bold"></i></button>
                  <button type="button" class="btn btn-outline-secondary" data-cmd="italic" title="Italic"><i class="bi bi-type-italic"></i></button>
                  <button type="button" class="btn btn-outline-secondary" data-cmd="underline" title="Underline"><i class="bi bi-type-underline"></i></button>
                  <button type="button" class="btn btn-outline-secondary" data-cmd="insertUnorderedList" title="Bulleted list"><i class="bi bi-list-ul"></i></button>
                  <button type="button" class="btn btn-outline-secondary" id="btnMakeLink" title="Add link"><i class="bi bi-link-45deg"></i></button>
                </div>
                <div class="text-end small muted"><span id="charNow">0</span> chars</div>
              </div>

              <div id="postEditor" class="editor-surface editor-placeholder" contenteditable="true"
                   data-placeholder="Write something about your episode…&#10;&#10;"></div>

              <div class="form-text mt-2">
                Tip: Insert your podcast app links, then “Enhance with AI”.
              </div>

              <div class="mt-3 d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-outline-secondary" id="btnInsertLinks">
                  <i class="bi bi-link-45deg me-1"></i> Insert podcast links
                </button>
                <button type="button" class="btn btn-outline-primary" id="btnEnhanceAi">
                  <i class="bi bi-stars me-1"></i> Enhance with AI
                </button>
                <button type="button" class="btn btn-outline-secondary" id="btnClearBody">Clear</button>
              </div>
            </div>

            {{-- Video upload (YouTube/TikTok only) --}}
            <div class="mt-4" id="videoUploadWrap" style="display:none;">
              <label class="form-label d-flex justify-content-between align-items-center">
                <span>Upload video (YouTube/TikTok)</span>
                <small class="text-muted">Max 512 MB · MP4/MOV/WEBM</small>
              </label>
              <div class="border rounded p-3 bg-light">
                <input class="form-control" type="file" name="video" id="videoInput" accept="video/mp4,video/quicktime,video/webm">
                <div class="small text-muted mt-2" id="videoMeta"></div>
              </div>
              <div class="form-text">
                If YouTube or TikTok is selected above, the video will be uploaded with this post text as the caption/description.
              </div>
            </div>

            {{-- Attachments --}}
            <div class="d-flex align-items-center gap-2 mt-3">
              <button type="button" class="btn btn-outline-secondary" id="btnAttachImage" title="Attach image(s)">
                <i class="bi bi-image"></i>
              </button>
              <button type="button" class="btn btn-outline-secondary" id="btnAttachVideo" title="Attach video">
                <i class="bi bi-camera-video"></i>
              </button>
              <button type="button" class="btn btn-outline-secondary" id="btnAttachLink" title="Insert link">
                <i class="bi bi-link-45deg"></i>
              </button>
            </div>
            <input class="d-none" type="file" id="imageInput" name="images[]" accept="image/*" multiple>
            <div id="imagePreviewStrip" class="d-flex flex-wrap gap-2 mt-2"></div>
          </div>

          <div class="modal-footer border-0">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-dark">
              <i class="bi bi-send me-1"></i> Create Post
            </button>
          </div>
        </form>

      </div>
    </div>
  </div>
@endpush

@push('scripts')
<script>
(() => {
  // ===== Editor & helpers =====
  const editor = document.getElementById('postEditor');
  const bodyField = document.getElementById('bodyField');
  const count = document.getElementById('charNow');

  const dirsJSON = document.getElementById('connectedDirectoriesJson')?.value || '[]';
  let dirs = [];
  try { dirs = JSON.parse(dirsJSON) || []; } catch(_) {}

  function getEditorText(){ return (editor?.innerText || '').trim(); }
  function syncCount(){ count.textContent = getEditorText().length; }
  function exec(cmd, val=null){
    editor.focus();
    document.execCommand(cmd, false, val);
    syncCount();
  }
  document.querySelectorAll('.composer-toolbar [data-cmd]').forEach(btn=>{
    btn.addEventListener('click', () => exec(btn.dataset.cmd));
  });
  document.getElementById('btnMakeLink')?.addEventListener('click', ()=>{
    const url = prompt('URL to link to:');
    if (url) exec('createLink', url);
  });

  // Insert podcast links
  function buildLinksBlock(){
    const includeConnected = document.getElementById('includeConnected')?.checked ?? true;
    const episodeUrl = document.querySelector('input[name="episode_url"]')?.value?.trim();
    const lines = [];
    if (episodeUrl) lines.push(`Listen here: ${episodeUrl}`);

    const items = (dirs || [])
      .filter(d => (includeConnected ? !!d.connected : true) && d.external_url)
      .map(d => `• ${d.name}: ${d.external_url}`);

    if (items.length){
      if (!episodeUrl) lines.push('Listen on your favorite app:');
      lines.push(...items);
    }
    return lines.join('\n');
  }
  function appendLinksToEditor(){
    const block = buildLinksBlock();
    if (!block) return;
    const para = block.replace(/\n/g, '<br>');
    const div = document.createElement('div');
    div.innerHTML = `<br>${para}`;
    editor.appendChild(div);
    syncCount();
  }
  document.getElementById('btnInsertLinks')?.addEventListener('click', appendLinksToEditor);

  // Clear editor
  document.getElementById('btnClearBody')?.addEventListener('click', ()=>{
    editor.innerHTML = '';
    syncCount();
  });

  editor?.addEventListener('input', syncCount);
  syncCount();

  // On submit: HTML -> plaintext into hidden field
  document.getElementById('socialPostForm')?.addEventListener('submit', ()=>{
    const html = editor.innerHTML
      .replace(/<br\s*\/?>/gi, '\n')
      .replace(/<\/(div|p|li|h\d)>/gi, '\n');
    const tmp = document.createElement('div'); tmp.innerHTML = html;
    bodyField.value = tmp.textContent.replace(/\n{3,}/g, '\n\n').trim();
  });

  // AI enhance
  document.getElementById('btnEnhanceAi')?.addEventListener('click', async () => {
    const token = document.querySelector('meta[name="csrf-token"]')?.content;
    const title = document.querySelector('input[name="title"]')?.value || '';
    const episodeUrl = document.querySelector('input[name="episode_url"]')?.value || '';
    const basePrompt = [
      'This is a social post for a podcast.',
      'Write a concise, engaging post with a friendly, professional tone.',
      'Prefer Australian English.',
      'Add 2–4 relevant hashtags; avoid spammy tags.',
      'Keep any URLs exactly as provided at the end.'
    ].join(' ');
    const userContent = [
      `Title: ${title || '(none)'}`,
      episodeUrl ? `Episode URL: ${episodeUrl}` : null,
      'Draft Body:',
      getEditorText() || '(empty)'
    ].filter(Boolean).join('\n');

    try{
      const res = await fetch(`{{ route('ai.enhance.social') }}`, {
        method: 'POST',
        headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': token },
        body: JSON.stringify({ prompt: basePrompt, input: userContent })
      });
      if (!res.ok) throw new Error('AI enhance failed');
      const data = await res.json();
      if (data?.text){
        editor.innerText = (data.text || '').trim();
        const links = buildLinksBlock();
        if (links) {
          const html = '\n\n' + links.replace(/\n/g, '<br>');
          const wrap = document.createElement('div');
          wrap.innerHTML = html;
          editor.appendChild(wrap);
        }
        syncCount();
      }
    } catch(e){
      console.error(e);
      alert('Sorry — AI enhancement failed. Please try again.');
    }
  });

  // ===== Service picker -> show/hide video upload =====
  const videoWrap = document.getElementById('videoUploadWrap');
  const videoInput = document.getElementById('videoInput');
  const videoMeta  = document.getElementById('videoMeta');

  function servicesSelected(){
    return Array.from(document.querySelectorAll('#servicesPicker input[type="checkbox"]:checked')).map(i=>i.value);
  }
  function updateVideoVisibility(){
    const svc = servicesSelected();
    const needsVideo = svc.includes('youtube') || svc.includes('tiktok');
    videoWrap.style.display = needsVideo ? '' : 'none';
    if (!needsVideo && videoInput) {
      videoInput.value = '';
      videoMeta.textContent = '';
    }
  }
  document.getElementById('servicesPicker')?.addEventListener('change', updateVideoVisibility);
  updateVideoVisibility();

  // Basic file info + size check (512 MB)
  videoInput?.addEventListener('change', () => {
    const f = videoInput.files?.[0];
    if (!f){ videoMeta.textContent=''; return; }
    const mb = (f.size/1048576).toFixed(1);
    videoMeta.textContent = `${f.name} • ${mb} MB`;
    if (f.size > 512*1024*1024) {
      alert('Please select a file under 512 MB.');
      videoInput.value = '';
      videoMeta.textContent = '';
    }
  });

  // ===== Attachment buttons =====
  const btnAttachImage = document.getElementById('btnAttachImage');
  const btnAttachVideo = document.getElementById('btnAttachVideo');
  const btnAttachLink  = document.getElementById('btnAttachLink');

  const imageInput     = document.getElementById('imageInput');
  const imageStrip     = document.getElementById('imagePreviewStrip');

  // Images: open picker
  btnAttachImage?.addEventListener('click', () => imageInput?.click());

  // Show thumbnails
  imageInput?.addEventListener('change', () => {
    imageStrip.innerHTML = '';
    const files = Array.from(imageInput.files || []);
    files.slice(0, 8).forEach(f => {
      const url = URL.createObjectURL(f);
      const img = document.createElement('img');
      img.src = url;
      img.alt = f.name;
      img.style.width = '64px';
      img.style.height = '64px';
      img.style.objectFit = 'cover';
      img.className = 'rounded border';
      imageStrip.appendChild(img);
    });
  });

  // Video: reveal section and open chooser
  btnAttachVideo?.addEventListener('click', () => {
    if (videoWrap) videoWrap.style.display = '';
    videoInput?.click();
  });

  // Link: prompt and insert
  btnAttachLink?.addEventListener('click', () => {
    const url = prompt('Paste a URL to insert:');
    if (!url) return;
    editor.focus();
    document.execCommand('createLink', false, url);
    document.execCommand('insertHTML', false, '<span> </span>');
  });
})();
</script>
@endpush
