{{-- resources/views/episodes/_modal_create.blade.php --}}
@push('styles')
<style>
  /* Wider modal & comfy spacing */
  #episodeModal .modal-dialog { max-width: 1100px; }
  #episodeModal .form-label { font-weight: 600; }
  #episodeModal .muted-hint { color:#6b7280; font-size:.85rem; }

  /* Simple drop-look box for file input (click to select) */
  .drop-tile {
    border: 1px dashed rgba(0,0,0,.25);
    border-radius: .75rem;
    padding: 1rem;
    background: #fafafa;
  }
  .drop-tile:hover { background: #f6f6f6; }
  .drop-tile .title { font-weight: 600; }
</style>
@endpush

<div class="modal fade" id="episodeModal" tabindex="-1" aria-labelledby="episodeModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <form method="POST" action="{{ route('episodes.store') }}" enctype="multipart/form-data">
        @csrf
        {{-- lets us auto-reopen on validation errors --}}
        <input type="hidden" name="_show_episode_modal" value="1">

        <div class="modal-header">
          <h5 class="modal-title" id="episodeModalLabel">
            <i class="bi bi-mic me-2"></i>New Episode
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
          {{-- Top: Title + Description full width on small screens, split on lg up --}}
          <div class="row g-4">
            <div class="col-lg-7">
              <div class="mb-3">
                <label class="form-label" for="ep-title">Title</label>
                <input id="ep-title" name="title" type="text" required
                       class="form-control @error('title') is-invalid @enderror"
                       value="{{ old('title') }}">
                @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>

              <div>
                <label class="form-label" for="ep-desc">Description</label>
                <textarea id="ep-desc" name="description" rows="10"
                          class="form-control @error('description') is-invalid @enderror"
                          placeholder="Write a short summary, show notes, links…">{{ old('description') }}</textarea>
                @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
            </div>

            {{-- Right column: Upload + meta --}}
            <div class="col-lg-5">
              <div class="drop-tile mb-3">
                <div class="d-flex align-items-start">
                  <i class="bi bi-music-note-beamed fs-4 me-3"></i>
                  <div>
                    <div class="title mb-1">Upload Audio</div>
                    <div class="muted-hint">
                      MP3 / M4A / WAV recommended. If you also provide a URL below, the uploaded file will be used.
                    </div>
                  </div>
                </div>

                <div class="mt-3">
                  <input class="form-control @error('audio') is-invalid @enderror" type="file" name="audio" accept="audio/*">
                  @error('audio') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label" for="ep-url">Audio URL (optional)</label>
                <input id="ep-url" name="audio_url" type="url"
                       placeholder="https://cdn.example.com/episode.mp3"
                       class="form-control @error('audio_url') is-invalid @enderror"
                       value="{{ old('audio_url') }}">
                @error('audio_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>

              <div class="row g-3">
                <div class="col-6">
                  <label class="form-label" for="ep-dur">Duration (sec)</label>
                  <input id="ep-dur" name="duration_seconds" type="number" min="0"
                         class="form-control @error('duration_seconds') is-invalid @enderror"
                         value="{{ old('duration_seconds') }}">
                  @error('duration_seconds') <div class="invalid-feedback">{{ $message }}</div> @enderror
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

              <div class="mt-3">
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

        <div class="modal-footer d-flex justify-content-between align-items-center">
          <div class="muted-hint">
            <i class="bi bi-info-circle me-1"></i>
            You can edit details or change status after creation.
          </div>
          <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-dark">
              <i class="bi bi-plus-lg me-1"></i>Create Episode
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>
