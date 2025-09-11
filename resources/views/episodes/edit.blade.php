{{-- resources/views/episodes/edit.blade.php --}}
@extends('layouts.app')

@section('title', 'Edit Episode')
@section('page-title', 'Edit Episode')

@section('content')
<style>
  .sticky-side { position: sticky; top: 82px; z-index: 5; }
  .cover-card  { border: 1px dashed rgba(0,0,0,.12); }
  /* Keep modals above sticky UI/backdrop */
  .modal, .modal.fade .modal-dialog { z-index: 3001 !important; }
  .modal-backdrop, .modal-backdrop.show { z-index: 3000 !important; }
</style>

@if (session('success'))
  <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if ($errors->any())
  <div class="alert alert-danger" role="alert">
    <div class="fw-semibold mb-2">We found a few issues:</div>
    <ul class="mb-0">
      @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
    </ul>
  </div>
@endif

@php
  $status = old('status', $episode->status ?? 'draft');
@endphp

<div class="row g-4">
  {{-- LEFT: main form --}}
  <div class="col-lg-9">
    <div class="section-card p-4">
      <form id="episodeForm" method="POST" action="{{ route('episodes.update', $episode) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        {{-- Title --}}
        <div class="mb-3">
          <label class="form-label">Title</label>
          <input name="title" type="text" required
                 class="form-control @error('title') is-invalid @enderror"
                 value="{{ old('title', $episode->title) }}">
          @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        {{-- Description --}}
        <div class="mb-3">
          <label class="form-label">Description</label>
          <textarea name="description" rows="8"
                    class="form-control @error('description') is-invalid @enderror">{{ old('description', $episode->description) }}</textarea>
          @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        {{-- Audio + URL --}}
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Upload Audio</label>
            <input type="file" name="audio" accept="audio/*"
                   class="form-control @error('audio') is-invalid @enderror">
            @error('audio') <div class="invalid-feedback">{{ $message }}</div> @enderror
            <small class="text-secondary d-block mt-1">If you also provide a URL, the uploaded file will be used.</small>
          </div>
          <div class="col-md-6">
           @php
              /** @var \App\Models\Episode $episode */
              // Only use old() if there are errors (i.e., we just failed validation)
              $audioUrl = $errors->any()
                  ? old('audio_url', $episode->audio_url)
                  : ($episode->audio_url ?? '');
            @endphp

            <label class="form-label">Audio URL</label>
            <input name="audio_url" type="url"
                  class="form-control @error('audio_url') is-invalid @enderror"
                  value="{{ $audioUrl }}">
            @error('audio_url') <div class="invalid-feedback">{{ $message }}</div> @enderror

          </div>
        </div>

        {{-- Status / Duration / Publish At --}}
        <div class="row g-3 mt-1">
          <div class="col-md-4">
            <label class="form-label">Status</label>
            {{-- IMPORTANT: id must be "statusField" so scripts can set it to "draft" --}}
            <select id="statusField" name="status" class="form-select @error('status') is-invalid @enderror">
              <option value="draft"     {{ $status === 'draft' ? 'selected' : '' }}>Draft</option>
              <option value="published" {{ $status === 'published' ? 'selected' : '' }}>Published</option>
            </select>
            @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-md-4">
            <label class="form-label">Duration (sec)</label>
            <input name="duration_seconds" type="number" min="0"
                   class="form-control @error('duration_seconds') is-invalid @enderror"
                   value="{{ old('duration_seconds', $episode->duration_seconds) }}">
            @error('duration_seconds') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-md-4">
            <label class="form-label">Publish At (optional)</label>
            <input name="published_at" type="datetime-local"
                   class="form-control @error('published_at') is-invalid @enderror"
                   value="{{ old('published_at', optional($episode->published_at)->format('Y-m-d\TH:i')) }}">
            @error('published_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
        </div>

        {{-- Upload progress (save/save-draft) --}}
        <div id="uploadProgressWrap" class="mt-3 d-none">
          <div id="uploadProgressLabel" class="small text-secondary mb-1">Preparing…</div>
          <div class="progress" style="height:8px;">
            <div id="uploadProgressBar" class="progress-bar bg-success"
                 role="progressbar" style="width:0%" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"></div>
          </div>
        </div>
      </form>
    </div>
  </div>

  {{-- RIGHT: sidebar --}}
  <div class="col-lg-3">
    <div class="sticky-side">
      {{-- Cover --}}
      <div class="section-card p-3 cover-card text-center">
        <div class="mb-2 fw-semibold">Episode Cover</div>
        @php $coverUrl = $episode->cover_image_url ?? 'https://placehold.co/480x480?text=Cover'; @endphp
        <img id="coverPreview" src="{{ $coverUrl }}" alt="Cover" class="img-fluid rounded mb-2">

        <form method="POST" action="{{ route('episodes.cover.upload', $episode) }}" enctype="multipart/form-data" class="d-grid gap-2">
          @csrf @method('PATCH')
          <input id="coverInput" type="file" name="cover" accept="image/png,image/jpeg,image/webp" class="form-control">
          @error('cover') <div class="text-danger small">{{ $message }}</div> @enderror
          <button class="btn btn-dark btn-sm w-100" type="submit"><i class="bi bi-upload me-1"></i>Upload image</button>
        </form>

        @if(!empty($episode->cover_path))
          <form method="POST" action="{{ route('episodes.cover.remove', $episode) }}" class="mt-2">
            @csrf @method('DELETE')
            <button class="btn btn-outline-danger btn-sm w-100" type="submit">
              <i class="bi bi-x-circle me-1"></i>Remove episode cover
            </button>
          </form>
        @endif

        <div class="text-secondary small mt-2">Between 1400 and 2048px square (jpg or png).</div>
      </div>

      {{-- Actions under cover --}}
      <div class="section-card p-3 mt-3">
        <h6 class="mb-3">Actions</h6>
        <div class="d-grid gap-2">
          <button id="updateBtn"    type="button" class="btn btn-blush"><i class="bi bi-save me-1"></i>Save Changes</button>
          <button id="saveDraftBtn" type="button" class="btn btn-outline-secondary">Save as draft</button>

          @if(strtolower($status) !== 'published')
            <button id="publishNowBtn" type="button" class="btn btn-success"><i class="bi bi-megaphone me-1"></i>Publish now</button>
          @else
            <button id="unpublishBtn" type="button" class="btn btn-outline-warning"><i class="bi bi-arrow-counterclockwise me-1"></i>Unpublish</button>
          @endif

          <button id="aiEnhanceBtn" type="button" class="btn btn-outline-blush"><i class="bi bi-stars me-1"></i>Enhance with AI</button>

          <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#chaptersModal">
            <i class="bi bi-journal-text me-1"></i>Edit chapter markers
          </button>
          <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#transcriptModal">
            <i class="bi bi-text-paragraph me-1"></i>Edit transcript
          </button>

          {{-- Actions (replace the old Cancel line with the two below) --}}
          <button id="aiCancelBtn" type="button" class="btn btn-outline-secondary d-none">
            Cancel enhance
          </button>
          <a id="navCancelBtn" class="btn btn-outline-secondary" href="{{ route('episodes') }}">
              Cancel
          </a>

          
          <button id="deleteEpisodeBtn" type="button" class="btn btn-outline-danger"><i class="bi bi-trash me-1"></i>Delete</button>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Helper forms (outside main form) --}}
<form id="publishForm"   method="POST" action="{{ route('episodes.publish', $episode) }}"   class="d-none">@csrf @method('PATCH')</form>
<form id="unpublishForm" method="POST" action="{{ route('episodes.unpublish', $episode) }}" class="d-none">@csrf @method('PATCH')</form>
<form id="deleteEpisodeForm" method="POST" action="{{ route('episodes.destroy', $episode) }}" class="d-none">@csrf @method('DELETE')</form>
<form id="aiEnhanceForm"   method="POST" action="{{ route('episodes.ai.enhance', $episode) }}" class="d-none">@csrf</form>

{{-- Modals --}}
@includeIf('episodes._modal_chapters')
@includeIf('episodes._modal_transcript')

{{-- AI progress (start + poll) --}}
<script>
  
(function () {
  const aiBtn    = document.getElementById('aiEnhanceBtn');
  const wrap     = document.getElementById('uploadProgressWrap');
  const bar      = document.getElementById('uploadProgressBar');
  const label    = document.getElementById('uploadProgressLabel');
  const startUrl = @json(route('episodes.ai.enhance', $episode));
  const pollUrl  = @json(route('episodes.ai.progress', $episode));
  const cancelUrl= @json(route('episodes.ai.cancel',   $episode)); // <-- add this


  function setBar(pct, text, danger=false) {
    wrap.classList.remove('d-none');
    bar.style.width = (pct||0) + '%';
    bar.setAttribute('aria-valuenow', pct||0);
    bar.classList.toggle('bg-danger', !!danger);
    bar.classList.toggle('bg-success', !danger);
    label.textContent = text || '';
    wrap.scrollIntoView({ behavior:'smooth', block:'center' });
  }

  let pollTimer = null;
  async function poll() {
    try {
      const res = await fetch(pollUrl, { credentials: 'same-origin' });
      const j = await res.json();
      const pct = Math.max(0, Math.min(100, j.progress ?? 0));
      setBar(pct, j.message || 'Working…');

      if (j.status === 'done') {
        setBar(100, 'Finished!');
        clearInterval(pollTimer);
        setTimeout(() => window.location.reload(), 800);
      } else if (j.status === 'failed') {
        clearInterval(pollTimer);
        setBar(0, j.message || 'AI failed.', true);
        aiBtn.disabled = false;
        aiBtn.innerHTML = '<i class="bi bi-stars me-1"></i>Enhance with AI';
      }
    } catch (e) {
      // swallow transient poll errors
    }
  }

  aiBtn?.addEventListener('click', async () => {
    aiBtn.disabled = true;
    aiBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Enhancing…';

    setBar(1, 'Queuing AI job…');

    const token = document.querySelector('#aiEnhanceForm input[name=_token]')?.value
               || document.querySelector('meta[name=csrf-token]')?.content;

    try {
      const res = await fetch(startUrl, {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': token,
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json'
        },
        credentials: 'same-origin'
      });

      if (![200,202].includes(res.status)) {
        const body = await res.text();
        throw new Error(body || ('HTTP ' + res.status));
      }

      // begin polling
      pollTimer = setInterval(poll, 1500);
      poll(); // immediate first poll
    } catch (err) {
      setBar(0, 'Failed to start AI: ' + (err?.message || err), true);
      aiBtn.disabled = false;
      aiBtn.innerHTML = '<i class="bi bi-stars me-1"></i>Enhance with AI';
    }
  });
})();
</script>

{{-- Ensure modals render on top (append to body) --}}
<script>
  ['chaptersModal','transcriptModal'].forEach(function(id){
    var el=document.getElementById(id);
    if(!el) return;
    el.addEventListener('show.bs.modal', function(){
      if(el.parentElement!==document.body) document.body.appendChild(el);
    });
    if(el.parentElement!==document.body) document.body.appendChild(el);
  });
</script>

{{-- XHR upload progress for Save / Draft --}}
<script>
(function(){
  const form   = document.getElementById('episodeForm');
  const status = document.getElementById('statusField');
  const save   = document.getElementById('updateBtn');
  const draft  = document.getElementById('saveDraftBtn');
  const aiBtn       = document.getElementById('aiEnhanceBtn');
  const aiCancelBtn = document.getElementById('aiCancelBtn');
  const navCancel   = document.getElementById('navCancelBtn');
  const wrap   = document.getElementById('uploadProgressWrap');
  const bar    = document.getElementById('uploadProgressBar');
  const label  = document.getElementById('uploadProgressLabel');

  function fmtBps(b){const u=['B/s','KB/s','MB/s','GB/s'];let i=0,v=b;while(v>=1024&&i<u.length-1){v/=1024;i++;}return (v>=100?v.toFixed(0):v>=10?v.toFixed(1):v.toFixed(2))+' '+u[i];}

  function submitWithProgress(){
    if(!('upload' in new XMLHttpRequest())){ form.submit(); return; }
    const fd=new FormData(form); if(!fd.has('_method')) fd.append('_method','PUT');
    [save,draft].forEach(b=>b&&(b.disabled=true));
    wrap.classList.remove('d-none'); bar.style.width='0%'; bar.setAttribute('aria-valuenow','0'); label.textContent='Starting upload…';

    const xhr=new XMLHttpRequest(); const started=Date.now();
    xhr.upload.onprogress=(e)=>{ if(!e.lengthComputable) return;
      const pct=Math.round((e.loaded/e.total)*100);
      const speed=e.loaded/((Date.now()-started)/1000);
      bar.style.width=pct+'%'; bar.setAttribute('aria-valuenow',String(pct));
      label.textContent=`Uploading… ${pct}% · ${fmtBps(speed)}`;
    };
    xhr.onload=()=>{ if([200,201,204,302].includes(xhr.status)){ bar.style.width='100%'; label.textContent='Processing…'; window.location.reload(); } else onError(); };
    xhr.onerror=onError;
    function onError(){ bar.classList.remove('bg-success'); bar.classList.add('bg-danger'); label.textContent='Upload failed. Please try again.'; [save,draft].forEach(b=>b&&(b.disabled=false)); }
    xhr.open('POST', form.action, true); xhr.setRequestHeader('X-Requested-With','XMLHttpRequest'); xhr.send(fd);
  }

  save?.addEventListener('click', (e)=>{ e.preventDefault(); submitWithProgress(); });
  draft?.addEventListener('click',(e)=>{ e.preventDefault(); if(status) status.value='draft'; submitWithProgress(); });
})();
</script>

{{-- Publish / Unpublish / Delete (NO AI handler here) + cover preview --}}
<script>

  
  document.getElementById('publishNowBtn')?.addEventListener('click', () =>
    document.getElementById('publishForm')?.submit()
  );
  document.getElementById('unpublishBtn')?.addEventListener('click', () =>
    document.getElementById('unpublishForm')?.submit()
  );
  document.getElementById('deleteEpisodeBtn')?.addEventListener('click', () => {
    if (confirm('Delete this episode permanently?')) {
      document.getElementById('deleteEpisodeForm')?.submit();
    }
  });

  document.getElementById('coverInput')?.addEventListener('change', (e)=>{
    const f=e.target.files && e.target.files[0];
    if(f) document.getElementById('coverPreview').src = URL.createObjectURL(f);
  });
</script>
@endsection
