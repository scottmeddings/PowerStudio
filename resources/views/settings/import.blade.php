@extends('layouts.app')

@section('title','Settings · Import to Podpower')
@section('page-title','Import to Podpower')

@section('content')
  <div class="section-card p-4">
    <form method="POST" action="{{ route('settings.import.handle') }}" class="row g-4" id="rssImportForm" autocomplete="off">
      @csrf

      <div class="col-12">
        <h5 class="mb-3">Step 1: Enter Your RSS Feed URL</h5>
        <label class="form-label fw-semibold">RSS Feed *</label>
        <input type="url"
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
          <span id="startText">Next</span>
        </button>

        {{-- Progress area --}}
        <div id="importProgress" class="flex-grow-1 d-none" style="max-width:520px;">
          <div class="progress" style="height:10px;">
            <div id="importBar" class="progress-bar" role="progressbar" style="width:0%"></div>
          </div>
          <div class="d-flex justify-content-between small text-muted mt-2">
            <span id="importMsg">Waiting to start…</span>
            <span id="importPct">0%</span>
          </div>
          <div class="small text-muted mt-1" id="importDebug" aria-live="polite"></div>
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
  const startedFromSession = {{ isset($started) && $started ? 'true' : 'false' }};

  const form   = document.getElementById('rssImportForm');
  const btn    = document.getElementById('startBtn');
  const spin   = document.getElementById('startSpin');
  const txt    = document.getElementById('startText');

  const wrap   = document.getElementById('importProgress');
  const bar    = document.getElementById('importBar');
  const pct    = document.getElementById('importPct');
  const msg    = document.getElementById('importMsg');
  const dbg    = document.getElementById('importDebug');

  function showProgress() { wrap.classList.remove('d-none'); }

  // Resilient poller with bounded backoff so it never “stops”
  let interval = 1000;      // start with 1s
  const maxInterval = 4000; // cap at 4s
  let tick = 0;

  function poll() {
    fetch("{{ route('settings.import.status') }}", { headers: { 'X-Requested-With':'XMLHttpRequest' }})
      .then(r => r.ok ? r.json() : Promise.reject(r.status))
      .then(({progress}) => {
        showProgress();
        const p = progress || {};
        const percent = Math.max(0, Math.min(100, Number(p.percent || 0)));
        bar.style.width = percent + '%';
        pct.textContent = percent + '%';
        msg.textContent = p.message || 'Starting…';

        dbg.textContent = 'Polling #' + (++tick) + ' • ' + new Date().toLocaleTimeString();

        // Reset backoff once we see any valid progress object
        interval = 1000;

        if (percent >= 100 || (p.message || '').toLowerCase().startsWith('failed')) {
          // Done or failed; re-enable button
          btn.disabled = false;
          spin.classList.add('d-none');
          txt.textContent = 'Next';
          return; // stop polling
        }
        setTimeout(poll, interval);
      })
      .catch(() => {
        // If status endpoint not ready or transient error, keep trying with backoff
        showProgress();
        msg.textContent = 'Waiting for worker…';
        dbg.textContent = 'Re-trying in ' + (interval/1000).toFixed(1) + 's • ' + new Date().toLocaleTimeString();
        interval = Math.min(maxInterval, interval + 500);
        setTimeout(poll, interval);
      });
  }

  // Submit UX
  form.addEventListener('submit', function () {
    btn.disabled = true;
    spin.classList.remove('d-none');
    txt.textContent = 'Working…';
    showProgress();
    // After redirect, session flag starts polling; but we also poll immediately now
  });

  // Always start polling on page load:
  // - if we were redirected (session flag), or
  // - if a job is already running in the background
  showProgress();
  if (startedFromSession) {
    msg.textContent = 'Starting…';
  }
  poll();
})();
</script>
@endpush
