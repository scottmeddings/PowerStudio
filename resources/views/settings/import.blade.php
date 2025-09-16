@extends('layouts.app')

@section('title','Settings · Import to Podpower')
@section('page-title','Import to Podpower')

@section('content')
  <div class="section-card p-4">
    <form method="POST" action="{{ route('settings.import.handle') }}" class="row g-4" id="rssImportForm" autocomplete="off" novalidate>
      @csrf

      <div class="col-12">
        <h5 class="mb-3">Step 1: Enter Your RSS Feed URL</h5>
        <label class="form-label fw-semibold" for="import_feed_url">RSS Feed <span class="text-danger">*</span></label>
        <input type="url"
               id="import_feed_url"
               name="import_feed_url"
               class="form-control @error('import_feed_url') is-invalid @enderror"
               value="{{ old('import_feed_url', $import_feed_url ?? 'https://podcast.powertime.au/feed.xml') }}"
               placeholder="https://example.com/feed.xml"
               required>
        @error('import_feed_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <div class="form-text">We’ll fetch titles, descriptions, audio files (downloaded to storage), images, and published dates.</div>
      </div>

      <div class="col-12">
        <h5 class="mb-2">Step 2: Import Options</h5>
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="do_301" id="do_301" value="1"
                 @checked(old('do_301', cache('settings:do_301', false)))>
          <label class="form-check-label" for="do_301">Set 301 redirect after import</label>
        </div>
        <div class="form-text">After import, we’ll guide you to set a 301 on your old host (e.g., Podbean).</div>
      </div>

      <div class="col-12 d-flex align-items-center gap-3">
        <button class="btn btn-blush d-inline-flex align-items-center gap-2" id="startBtn" type="submit">
          <span class="spinner-border spinner-border-sm d-none" id="startSpin" role="status" aria-hidden="true"></span>
          <span id="startText">Start import</span>
        </button>

        {{-- Progress area --}}
        <div id="importProgress" class="flex-grow-1 d-none" style="max-width:520px;" aria-live="polite">
          <div class="progress" style="height:10px;" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-labelledby="importMsg">
            <div id="importBar" class="progress-bar" style="width:0%"></div>
          </div>
          <div class="d-flex justify-content-between small text-muted mt-2">
            <span id="importMsg">Waiting to start…</span>
            <span id="importPct">0%</span>
          </div>
          <div class="small text-muted mt-1" id="importDebug"></div>
          <div class="small text-warning mt-1 d-none" id="importHint">
            Tip: if this stays on “Waiting for worker…”, ensure your queue worker is running:
            <code>php artisan queue:work --queue=default --timeout=1800 --sleep=1 --tries=1</code>
          </div>
        </div>
      </div>
    </form>

    <noscript>
      <div class="alert alert-warning mt-3">Enable JavaScript to view live import progress.</div>
    </noscript>
  </div>
@endsection

@push('scripts')
<script>
(function () {
  // Resume only if the controller flashed the session flag after a successful dispatch
  const startedFromSession = @json(session('rss_import_started', $started ?? false));

  // UI hooks
  const form = document.getElementById('rssImportForm');
  const btn  = document.getElementById('startBtn');
  const spin = document.getElementById('startSpin');
  const txt  = document.getElementById('startText');

  const wrap = document.getElementById('importProgress');
  const bar  = document.getElementById('importBar');
  const pct  = document.getElementById('importPct');
  const msg  = document.getElementById('importMsg');
  const dbg  = document.getElementById('importDebug');
  const hint = document.getElementById('importHint'); // optional; guard below

  const STATUS_URL = "{{ route('settings.import.status') }}";

  function showProgress() { wrap?.classList.remove('d-none'); }
  function setPct(n) {
    const v = Math.max(0, Math.min(100, Number(n || 0)));
    if (bar) bar.style.width = v + '%';
    if (pct) pct.textContent = v + '%';
    if (bar?.parentElement) bar.parentElement.setAttribute('aria-valuenow', String(v));
  }

  // Resilient poller with bounded backoff + gentle diagnostics
  let interval = 1000;          // start at 1s
  const maxInterval = 4000;     // cap at 4s
  let tick = 0;
  let firstSeen = false;
  let consecutiveErrors = 0;
  let stopped = false;

  async function poll() {
    if (stopped) return;
    try {
      const r = await fetch(STATUS_URL, {
        headers: { 'X-Requested-With':'XMLHttpRequest' },
        credentials: 'same-origin'
      });
      if (!r.ok) {
        const text = await r.text().catch(()=> '');
        throw new Error('HTTP '+r.status+' '+(text?.slice(0,120) || ''));
      }
      const data = await r.json();
      const p = (data && data.progress) ? data.progress : {};

      if (!firstSeen) { showProgress(); firstSeen = true; }
      consecutiveErrors = 0;
      if (hint) hint.classList.add('d-none');

      setPct(p.percent);
      if (msg) msg.textContent = p.message || 'Starting…';
      if (dbg) dbg.textContent = 'Polling #'+ (++tick) +' • '+ new Date().toLocaleTimeString();

      interval = 1000; // reset backoff after a good read

      const done = (Number(p.percent) >= 100) || String(p.message || '').toLowerCase().startsWith('failed');
      if (done) {
        btn && (btn.disabled = false);
        spin && spin.classList.add('d-none');
        txt  && (txt.textContent = 'Start import');
        stopped = true;
        return;
      }
    } catch (err) {
      if (!firstSeen) { showProgress(); firstSeen = true; }
      consecutiveErrors++;
      if (msg) msg.textContent = 'Waiting for worker…';
      if (dbg) dbg.textContent = 'Retry in ' + (interval/1000).toFixed(1) + 's • ' + new Date().toLocaleTimeString() +
                                 (err && err.message ? (' • ' + err.message) : '');
      if (hint && consecutiveErrors >= 6) hint.classList.remove('d-none');
      interval = Math.min(maxInterval, interval + 500);
    }
    setTimeout(poll, interval);
  }

  // Submit UX — do NOT show progress yet; wait for redirect with session flag
  if (form) {
    form.addEventListener('submit', function () {
      if (btn)  btn.disabled = true;
      if (spin) spin.classList.remove('d-none');
      if (txt)  txt.textContent = 'Working…';
      // No showProgress(); we only poll after redirect confirms job was queued
    }, { passive: true });
  }

  // Resume mode: only start polling if we actually queued the job
  if (startedFromSession) {
    showProgress();
    if (msg) msg.textContent = 'Starting…';
    poll();
  }
})();
</script>

@endpush
