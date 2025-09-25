@push('styles')
<style>
  #episodeModal .modal-dialog { max-width: 1100px; }
  #episodeModal .form-label { font-weight: 600; }
  #episodeModal .muted-hint { color: #6b7280; font-size: .85rem; }
  .drop-tile {
    border: 1px dashed rgba(0,0,0,.25);
    border-radius: .75rem;
    padding: 1rem;
    background: #fafafa;
  }
  .drop-tile:hover { background: #f6f6f6; }
  .drop-tile .title { font-weight: 600; }

  /* Match page's compact style */
  #episodeModal .modal-content.section-card.compact {
    padding: .5rem .5rem 0;
    border-radius: .6rem;
  }
  #episodeModal .modal-header {
    background-color: #f8f9fa; /* Matches table-light */
    padding: .5rem .5rem;
  }
  #episodeModal .modal-body {
    padding: .5rem;
  }
  #episodeModal .modal-footer {
    background-color: #f8f9fa;
    padding: .5rem;
    border-top: 1px solid #e9ecef;
  }
  #episodeModal .btn-xs {
    --bs-btn-padding-y: .30rem;
    --bs-btn-padding-x: .62rem;
    --bs-btn-font-size: .80rem;
    line-height: 1.15;
  }
  #episodeModal .badge-compact {
    font-size: .72rem;
    font-weight: 600;
    padding: .28rem .45rem;
  }
</style>
@endpush

@php
  // If validation returned seconds, show it as minutes in the visible box.
  $oldSec = old('duration_seconds');
  $oldMin = is_numeric($oldSec) ? (int) ceil($oldSec / 60) : '';
@endphp

<div class="modal fade" id="episodeModal" tabindex="-1" aria-labelledby="episodeModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content section-card compact">
      <form id="createEpisodeForm" method="POST" action="{{ route('episodes.store') }}" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="_show_episode_modal" value="1">

        {{-- Hidden seconds that your controller expects --}}
        <input type="hidden" id="ep-dur-seconds" name="duration_seconds" value="{{ old('duration_seconds') }}">

        <div class="modal-header">
          <h5 class="modal-title" id="episodeModalLabel">
            <i class="bi bi-mic me-2"></i>New Episode
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
          <div class="row g-2">
            {{-- Left: Title + Description --}}
            <div class="col-lg-7">
              <div class="mb-2">
                <label class="form-label" for="ep-title">Title</label>
                <input id="ep-title" name="title" type="text" required
                       class="form-control @error('title') is-invalid @enderror"
                       value="{{ old('title') }}">
                @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>

              <div class="mb-2">
                <label class="form-label" for="ep-desc">Description</label>
                <textarea id="ep-desc" name="description" rows="10"
                          class="form-control @error('description') is-invalid @enderror"
                          placeholder="Write a short summary, show notes, links…">{{ old('description') }}</textarea>
                @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
            </div>

            {{-- Right: Upload + meta --}}
            <div class="col-lg-5">
              <div class="drop-tile mb-2">
                <div class="d-flex align-items-start">
                  <i class="bi bi-music-note-beamed fs-4 me-3"></i>
                  <div>
                    <div class="title mb-1">Upload MP3 Podcast</div>
                    <div class="muted-hint"></div>
                  </div>
                </div>

                <div class="mt-2">
                  <input
                    id="ep-audio"
                    class="form-control @error('audio') is-invalid @enderror"
                    type="file"
                    name="audio"
                    accept=".mp3,audio/mpeg"
                    required
                  >
                  @error('audio') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>

                {{-- Inline progress (optional; shown during upload) --}}
                <div id="createInlineProgress" class="mt-2 d-none">
                  <div class="small text-secondary mb-1" id="createProgressLabel">Uploading…</div>
                  <div class="progress" style="height:6px;">
                    <div id="createProgressBar" class="progress-bar" role="progressbar"
                         style="width:0%" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"></div>
                  </div>
                </div>
              </div>

              <div class="row g-2">
                <div class="col-6">
                  <label class="form-label" for="ep-dur-min">Duration (min)</label>
                  <input id="ep-dur-min" type="number" min="0" step="1"
                         class="form-control @error('duration_seconds') is-invalid @enderror"
                         value="{{ $oldMin }}">
                  @error('duration_seconds') <div class="invalid-feedback">{{ $message }}</div> @enderror
                  <div id="ep-dur-hint" class="muted-hint mt-1"></div>
                </div>

                <div class="col-6">
                  <label class="form-label" for="ep-status">Status</label>
                  @php $status = old('status','draft'); @endphp
                  <select id="ep-status" name="status" class="form-select @error('status') is-invalid @enderror">
                    <option value="draft"     {{ $status==='draft'?'selected':'' }}>Draft</option>
                    <option value="published" {{ $status==='published'?'selected':'' }}>Published</option>
                  </select>
                  @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
              </div>

              <div class="mt-2">
                <label class="form-label" for="ep-pub">Publish At (optional)</label>
                <input id="ep-pub" name="published_at" type="datetime-local"
                       class="form-control @error('published_at') is-invalid @enderror"
                       value="{{ old('published_at') }}">
                @error('published_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
                <div class="muted-hint mt-1">Leave blank to publish immediately (when status is Published).</div>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer bg-light p-2 d-flex justify-content-between align-items-center">
          <div class="muted-hint">
            <i class="bi bi-info-circle me-1"></i>
            You can edit details or change status after creation.
          </div>
          <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary btn-xs" data-bs-dismiss="modal">Cancel</button>

            {{-- Button spinner (Bootstrap) --}}
            <button type="submit" class="btn btn-dark btn-xs" id="createEpisodeBtn">
              <span class="spinner-border spinner-border-sm me-2 d-none" id="createEpisodeSpin" role="status" aria-hidden="true"></span>
              <span id="createEpisodeLabel"><i class="bi bi-plus-lg me-1"></i>Create Episode</span>
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

@push('scripts')
<script>
(function () {
  const form   = document.getElementById('createEpisodeForm');
  const btn    = document.getElementById('createEpisodeBtn');
  const spin   = document.getElementById('createEpisodeSpin');
  const label  = document.getElementById('createEpisodeLabel');

  // Inline progress
  const progWrap = document.getElementById('createInlineProgress');
  const progBar  = document.getElementById('createProgressBar');
  const progLbl  = document.getElementById('createProgressLabel');

  // Duration elements
  const durMinInp = document.getElementById('ep-dur-min');
  const durSecInp = document.getElementById('ep-dur-seconds');
  const durHint   = document.getElementById('ep-dur-hint');
  const fileInp   = document.getElementById('ep-audio');

  const fmtHMS = (total) => {
    const s = Math.max(0, Math.round(total || 0));
    const h = Math.floor(s/3600), m = Math.floor((s%3600)/60), sec = s%60;
    return h>0 ? `${h}:${String(m).padStart(2,'0')}:${String(sec).padStart(2,'0')}`
               : `${m}:${String(sec).padStart(2,'0')}`;
  };

  // --- Auto-detect duration from MP3 and fill minutes + seconds ---
  async function readDurationFromFile(file){
    return new Promise((resolve, reject) => {
      try {
        const url = URL.createObjectURL(file);
        const au  = new Audio();
        au.preload = 'metadata';
        au.src = url;
        const cleanup = () => { URL.revokeObjectURL(url); au.remove(); };
        au.addEventListener('loadedmetadata', () => {
          const d = au.duration;
          cleanup();
          resolve(isFinite(d) ? d : NaN);
        }, { once: true });
        au.addEventListener('error', () => { cleanup(); reject(new Error('metadata error')); }, { once: true });
      } catch (e) { reject(e); }
    });
  }

  fileInp?.addEventListener('change', async (e) => {
    const f = e.target.files && e.target.files[0];
    if (!f) return;
    if (durHint) durHint.textContent = 'Reading duration…';
    try {
      const dSec = await readDurationFromFile(f);
      if (isFinite(dSec) && dSec > 0) {
        const mins = Math.max(0, Math.round(dSec / 60)); // round to nearest minute
        if (durMinInp) durMinInp.value = mins;
        if (durSecInp) durSecInp.value = Math.round(dSec);
        if (durHint)   durHint.textContent = `Detected ${fmtHMS(dSec)} (${mins} min)`;
      } else {
        if (durHint) durHint.textContent = 'Could not determine duration.';
      }
    } catch (_) {
      if (durHint) durHint.textContent = 'Could not determine duration.';
    }
  });

  // Keep hidden seconds in sync if user edits minutes manually
  durMinInp?.addEventListener('input', () => {
    const mins = parseInt(durMinInp.value || '0', 10);
    if (durSecInp) durSecInp.value = Math.max(0, mins) * 60;
  });

  // --- Submit with inline progress + button spinner (no overlay) ---
  function submitWithProgress(e){
    if (!('upload' in new XMLHttpRequest())) return; // fallback to normal submit if no XHR progress
    e.preventDefault();

    // Ensure seconds reflects minutes before submit
    const mins = parseInt(durMinInp?.value || '0', 10);
    if (durSecInp) durSecInp.value = Math.max(0, mins) * 60;

    // Button spinner
    btn.disabled = true;
    spin.classList.remove('d-none');
    label.textContent = 'Creating…';

    // Show inline progress
    progWrap.classList.remove('d-none');
    progBar.style.width = '0%';
    progBar.setAttribute('aria-valuenow','0');
    progLbl.textContent = 'Uploading MP3…';

    const fd  = new FormData(form);
    const xhr = new XMLHttpRequest();
    const started = Date.now();

    xhr.upload.onprogress = (ev) => {
      if (!ev.lengthComputable) return;
      const pct = Math.round((ev.loaded / ev.total) * 100);
      progBar.style.width = pct + '%';
      progBar.setAttribute('aria-valuenow', String(pct));
      progLbl.textContent = 'Uploading MP3… ' + pct + '%';
    };

    xhr.onload = () => {
      if ([200,201,202,204,302].includes(xhr.status)) {
        try {
          const loc = xhr.getResponseHeader('Location');
          if (loc) window.location.href = loc; else window.location.reload();
        } catch { window.location.reload(); }
      } else {
        onError();
      }
    };
    xhr.onerror = onError;

    function onError(){
      btn.disabled = false;
      spin.classList.add('d-none');
      label.innerHTML = '<i class="bi bi-plus-lg me-1"></i>Create Episode';
      progLbl.textContent = 'Upload failed. Please try again.';
      progBar.classList.add('bg-danger');
    }

    xhr.open('POST', form.action, true);
    xhr.setRequestHeader('X-Requested-With','XMLHttpRequest');
    xhr.send(fd);
  }

  form.addEventListener('submit', submitWithProgress);
})();
</script>
@endpush