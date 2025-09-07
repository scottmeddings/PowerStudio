{{-- resources/views/episodes/edit.blade.php --}}
@extends('layouts.app')

@section('title', 'Edit Episode')
@section('page-title', 'Edit Episode')

@section('content')
  {{-- Flash --}}
  @if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif

  {{-- Validation --}}
  @if ($errors->any())
    <div class="alert alert-danger" role="alert">
      <div class="fw-semibold mb-2">We found a few issues:</div>
      <ul class="mb-0">
        @foreach ($errors->all() as $e)
          <li>{{ $e }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="section-card p-4">
    {{-- MAIN UPDATE FORM (PUT) — DO NOT put another <form> inside the partial --}}
    <form id="episodeForm"
          method="POST"
          action="{{ route('episodes.update', $episode) }}"
          enctype="multipart/form-data">
      @csrf
      @method('PUT')

      @include('episodes._form', ['episode' => $episode])
      {{-- The partial should provide:
           - hidden <input id="statusField" name="status" ...>
           - buttons with ids: updateBtn (type="button"), saveDraftBtn (type="button")
           - optional buttons: publishNowBtn, unpublishBtn, deleteEpisodeBtn, aiEnhanceBtn
      --}}
    </form>

    {{-- Helper forms (kept OUTSIDE the main form) --}}
    <form id="publishForm" method="POST" action="{{ route('episodes.publish', $episode) }}" class="d-none">
      @csrf @method('PATCH')
    </form>

    <form id="unpublishForm" method="POST" action="{{ route('episodes.unpublish', $episode) }}" class="d-none">
      @csrf @method('PATCH')
    </form>

    <form id="deleteEpisodeForm" method="POST" action="{{ route('episodes.destroy', $episode) }}" class="d-none">
      @csrf @method('DELETE')
    </form>

    <form id="aiEnhanceForm" method="POST" action="{{ route('episodes.ai.enhance', $episode) }}" class="d-none">
      @csrf
    </form>

    {{-- Upload progress (shown while saving with files) --}}
    <div id="uploadProgressWrap" class="mt-3 d-none">
      <div id="uploadProgressLabel" class="small text-secondary mb-1">Preparing…</div>
      <div class="progress" style="height:8px;">
        <div id="uploadProgressBar"
             class="progress-bar bg-success"
             role="progressbar"
             style="width:0%"
             aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"></div>
      </div>
    </div>
  </div>

  {{-- Optional modals --}}
  @include('episodes._modal_chapters')
  @include('episodes._modal_transcript')
@endsection

@push('scripts')
<script>
(function () {
  // ---- Elements ----
  const form         = document.getElementById('episodeForm');
  const statusField  = document.getElementById('statusField');     // provided by the partial
  const btnSave      = document.getElementById('updateBtn');       // type="button"
  const btnDraft     = document.getElementById('saveDraftBtn');    // type="button"
  const publishBtn   = document.getElementById('publishNowBtn');
  const unpublishBtn = document.getElementById('unpublishBtn');
  const deleteBtn    = document.getElementById('deleteEpisodeBtn');
  const aiBtn        = document.getElementById('aiEnhanceBtn');

  const wrap  = document.getElementById('uploadProgressWrap');
  const bar   = document.getElementById('uploadProgressBar');
  const label = document.getElementById('uploadProgressLabel');

  // ---- Helpers ----
  function fmtBytesPerSec(bps) {
    const u = ['B/s','KB/s','MB/s','GB/s'];
    let i = 0, v = bps;
    while (v >= 1024 && i < u.length - 1) { v /= 1024; i++; }
    return (v >= 100 ? v.toFixed(0) : v >= 10 ? v.toFixed(1) : v.toFixed(2)) + ' ' + u[i];
  }

  // Main XHR submit with progress
  function submitWithProgress() {
    if (!form) return;

    // Fallback if upload progress not supported
    if (!('upload' in new XMLHttpRequest())) { form.submit(); return; }

    const fd = new FormData(form); // includes _token and any files
    if (!fd.has('_method')) fd.append('_method','PUT'); // ensure method spoofing

    [btnSave, btnDraft].forEach(b => b && (b.disabled = true));

    wrap.classList.remove('d-none');
    bar.style.width = '0%';
    bar.setAttribute('aria-valuenow', '0');
    bar.classList.remove('bg-danger');
    bar.classList.add('bg-success');
    label.textContent = 'Starting upload…';
    wrap.scrollIntoView({ behavior:'smooth', block:'center' });

    const xhr = new XMLHttpRequest();
    const startedAt = Date.now();

    xhr.upload.onprogress = (e) => {
      if (!e.lengthComputable) return;
      const pct  = Math.round((e.loaded / e.total) * 100);
      const secs = (Date.now() - startedAt) / 1000;
      const speed = e.loaded / secs;
      const remaining = e.total - e.loaded;
      const eta = speed ? Math.max(0, remaining / speed) : 0;

      bar.style.width = pct + '%';
      bar.setAttribute('aria-valuenow', String(pct));
      label.textContent = `Uploading… ${pct}% · ${fmtBytesPerSec(speed)} · ~${Math.ceil(eta)}s left`;
    };

    xhr.onload = () => {
      if ([200,201,204,302].includes(xhr.status)) {
        bar.style.width = '100%';
        bar.setAttribute('aria-valuenow', '100');
        label.textContent = 'Processing…';
        window.location.reload();
      } else { onError(); }
    };

    xhr.onerror = onError;
    function onError(){
      bar.classList.remove('bg-success');
      bar.classList.add('bg-danger');
      label.textContent = 'Upload failed. Please try again.';
      [btnSave, btnDraft].forEach(b => b && (b.disabled = false));
    }

    xhr.open('POST', form.action, true);
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.send(fd);
  }

  // ---- Button bindings (Save / Draft use XHR with progress) ----
  btnSave?.addEventListener('click', (e) => {
    e.preventDefault();
    submitWithProgress();
  });
  btnDraft?.addEventListener('click', (e) => {
    e.preventDefault();
    if (statusField) statusField.value = 'draft';
    submitWithProgress();
  });

  // ---- Publish / Unpublish / Delete (simple posts) ----
  publishBtn?.addEventListener('click', () => {
    document.getElementById('publishForm')?.submit();
  });
  unpublishBtn?.addEventListener('click', () => {
    document.getElementById('unpublishForm')?.submit();
  });
  deleteBtn?.addEventListener('click', () => {
    if (confirm('Delete this episode permanently? This cannot be undone.')) {
      document.getElementById('deleteEpisodeForm')?.submit();
    }
  });

  // ---- Enhance with AI ----
  aiBtn?.addEventListener('click', () => {
    aiBtn.disabled = true;
    aiBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Enhancing…';
    document.getElementById('aiEnhanceForm')?.submit();
  });
})();
</script>
@endpush
