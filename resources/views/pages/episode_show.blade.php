{{-- resources/views/episodes/edit.blade.php --}}
@extends('layouts.app')

@section('title', 'Publish Episode')
@section('page-title', 'Publish Episode')

@push('styles')
<style>
  .label-sm{ font-size:.825rem; color:#6b7280; }
  .field-title{ font-weight:600; }
  .sticky-side{ position:sticky; top:82px; }
  .cover-card{ border:1px dashed rgba(0,0,0,.12); }
  .btn-split { position:relative; }
  .btn-split .dropdown-toggle-split{ border-left:0; }
  .muted-hint{ color:#6b7280; font-size:.8rem; }
</style>
@endpush

@section('content')
  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif

  @if($errors->any())
    <div class="alert alert-danger mb-3">
      <strong>We found a few issues:</strong>
      <ul class="mb-0">
        @foreach($errors->all() as $e)
          <li>{{ $e }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="row g-4">
    {{-- Main form (UPDATE) --}}
    <div class="col-lg-9">
      <form id="episodeForm" method="POST" action="{{ route('episodes.update', $episode) }}">
        @csrf
        @method('PUT')

        <input type="hidden" id="statusField" name="status" value="{{ old('status', $episode->status ?? 'draft') }}">

        <div class="section-card p-3 p-lg-4">
          {{-- File name / audio URL --}}
          <div class="mb-3">
            <div class="d-flex align-items-center justify-content-between">
              <label class="label-sm mb-1">File Name</label>
              <button class="btn btn-link btn-sm p-0" type="button" data-bs-toggle="collapse" data-bs-target="#editAudioUrl">
                Edit
              </button>
            </div>

            <div class="form-control bg-light" aria-readonly="true">
              {{ old('audio_url', $episode->audio_url ?? '—') }}
            </div>

            <div class="collapse mt-2" id="editAudioUrl">
              <input
                type="url"
                name="audio_url"
                class="form-control @error('audio_url') is-invalid @enderror"
                value="{{ old('audio_url', $episode->audio_url) }}"
                placeholder="https://cdn.example.com/path/episode.mp3">
              @error('audio_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
              <div class="muted-hint mt-1">Use a direct URL to your MP3 (or wire an uploader later).</div>
            </div>
          </div>

          {{-- Title --}}
          <div class="mb-3">
            <label class="label-sm mb-1" for="title">Title</label>
            <input
              id="title"
              type="text"
              name="title"
              required
              maxlength="160"
              class="form-control @error('title') is-invalid @enderror"
              value="{{ old('title', $episode->title) }}">
            @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>

          {{-- Description --}}
          <div class="mb-4">
            <label class="label-sm mb-1" for="description">Description</label>
            <textarea
              id="description"
              name="description"
              rows="10"
              class="form-control @error('description') is-invalid @enderror"
              placeholder="Write a great description for this episode…">{{ old('description', $episode->description) }}</textarea>
            @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
  

          {{-- More Episode Settings --}}
          <div class="border-top pt-3">
            <button class="btn btn-link p-0 mb-2" type="button" data-bs-toggle="collapse" data-bs-target="#moreSettings">
              More Episode Settings
              <i class="bi bi-chevron-down ms-1"></i>
            </button>

            <div class="collapse" id="moreSettings">
              <div class="row g-3">
                <div class="col-sm-6">
                  <label class="label-sm mb-1" for="duration_seconds">Duration (seconds)</label>
                  <input
                    id="duration_seconds"
                    type="number"
                    min="0"
                    name="duration_seconds"
                    class="form-control @error('duration_seconds') is-invalid @enderror"
                    value="{{ old('duration_seconds', $episode->duration_seconds) }}">
                  @error('duration_seconds') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-sm-6">
                  <label class="label-sm mb-1" for="published_at">Publish date (optional)</label>
                  <input
                    id="published_at"
                    type="datetime-local"
                    name="published_at"
                    class="form-control @error('published_at') is-invalid @enderror"
                    value="{{ old('published_at', optional($episode->published_at)->format('Y-m-d\TH:i')) }}">
                  @error('published_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
              </div>
            </div>
          </div>

          {{-- Actions --}}
          <div class="d-flex flex-wrap gap-2 mt-4">
            {{-- Save as draft (sets status to draft then submits) --}}
            <button id="saveDraftBtn" type="button" class="btn btn-outline-secondary">
              Save as draft
            </button>

            {{-- Update + split actions --}}
            <div class="btn-group btn-split">
              <button id="updateBtn" type="submit" class="btn btn-blush">
                Update
              </button>
              <button type="button" class="btn btn-blush dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="visually-hidden">Toggle options</span>
              </button>
              <ul class="dropdown-menu dropdown-menu-end">
                @if(strtolower($episode->status) !== 'published')
                  <li>
                    <button type="button" class="dropdown-item" id="publishNowBtn">
                      <i class="bi bi-megaphone me-2"></i>Publish now
                    </button>
                  </li>
                @else
                  <li>
                    <button type="button" class="dropdown-item" id="unpublishBtn">
                      <i class="bi bi-arrow-counterclockwise me-2"></i>Unpublish
                    </button>
                  </li>
                @endif
              </ul>
            </div>

            <div class="ms-auto muted-hint align-self-center">
              @if($episode->published_at)
                Published on: {{ $episode->published_at->format('Y-m-d H:i') }}
              @else
                Status: <span class="text-uppercase">{{ $episode->status }}</span>
              @endif
            </div>
          </div>
        </div>
      </form>

      {{-- Hidden forms (outside main form!) for publish/unpublish --}}
      <form id="publishForm" action="{{ route('episodes.publish', $episode) }}" method="POST" class="d-none">
        @csrf @method('PATCH')
      </form>
      <form id="unpublishForm" action="{{ route('episodes.unpublish', $episode) }}" method="POST" class="d-none">
        @csrf @method('PATCH')
      </form>
    </div>

    {{-- Sidebar --}}
    <div class="col-lg-3">
      <div class="sticky-side">
        {{-- Episode Cover --}}
        <div class="section-card p-3 cover-card text-center">
          <div class="mb-2 fw-semibold">Episode Cover</div>
          @php
            $coverUrl = $episode->cover_image_url ?? 'https://placehold.co/480x480?text=Cover';
          @endphp
          <img id="coverPreview" src="{{ $coverUrl }}" alt="Cover" class="img-fluid rounded mb-2">

          {{-- Upload/replace cover --}}
          <form method="POST" action="{{ route('episodes.cover.upload', $episode) }}" enctype="multipart/form-data" class="d-grid gap-2">
            @csrf
            @method('PATCH')
            <input id="coverInput" type="file" name="cover" accept="image/png,image/jpeg,image/webp" class="form-control">
            @error('cover') <div class="text-danger small">{{ $message }}</div> @enderror

            <button class="btn btn-dark btn-sm w-100" type="submit">
              <i class="bi bi-upload me-1"></i>Upload image
            </button>
          </form>

          {{-- Remove episode cover --}}
          @if(!empty($episode->cover_path))
            <form method="POST" action="{{ route('episodes.cover.remove', $episode) }}" class="mt-2">
              @csrf
              @method('DELETE')
              <button class="btn btn-outline-danger btn-sm w-100" type="submit">
                <i class="bi bi-x-circle me-1"></i>Remove episode cover
              </button>
            </form>
          @endif

          <div class="muted-hint mt-2">Between 1400 and 2048px square (jpg or png).</div>
        </div>

        {{-- Chapters / Transcript --}}
        <div class="section-card p-3 mt-3">
          <a href="#" data-bs-toggle="modal" data-bs-target="#chaptersModal"
             class="d-flex justify-content-between align-items-center text-decoration-none">
            <span>Episode Chapter Markers</span>
            <i class="bi bi-arrow-right"></i>
          </a>
          <hr class="my-2">
          <a href="#" data-bs-toggle="modal" data-bs-target="#transcriptModal"
             class="d-flex justify-content-between align-items-center text-decoration-none">
            <span>Episode Transcripts</span>
            <i class="bi bi-arrow-right"></i>
          </a>
        </div>
      </div>
    </div>
  </div>
@endsection

@push('scripts')
<script>
  (function(){
    const statusField = document.getElementById('statusField');
    const saveDraft   = document.getElementById('saveDraftBtn');
    const updateBtn   = document.getElementById('updateBtn');
    const form        = document.getElementById('episodeForm');

    if (saveDraft){
      saveDraft.addEventListener('click', function(){
        statusField.value = 'draft';
        form.submit();
      });
    }

    if (updateBtn){
      updateBtn.addEventListener('click', function(){
        if(!statusField.value){ statusField.value = '{{ $episode->status ?? 'draft' }}'; }
      });
    }

    // Publish / Unpublish
    document.getElementById('publishNowBtn')?.addEventListener('click', function(){
      document.getElementById('publishForm').submit();
    });
    document.getElementById('unpublishBtn')?.addEventListener('click', function(){
      document.getElementById('unpublishForm').submit();
    });

    // Local preview for episode cover
    const coverInput = document.getElementById('coverInput');
    const coverPreview = document.getElementById('coverPreview');
    if (coverInput && coverPreview){
      coverInput.addEventListener('change', function (e) {
        const file = e.target.files && e.target.files[0];
        if (file){
          const url = URL.createObjectURL(file);
          coverPreview.src = url;
        }
      });
    }
  })();
</script>
@endpush

{{-- Modals (provide these partials) --}}
@include('episodes._modal_chapters')
@include('episodes._modal_transcript')
