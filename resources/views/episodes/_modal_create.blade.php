{{-- resources/views/episodes/_modal_create.blade.php --}}
<div class="modal fade" id="episodeModal" tabindex="-1" aria-labelledby="episodeModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <form method="POST" action="{{ route('episodes.store') }}">
        @csrf
        {{-- lets us auto-reopen on validation errors --}}
        <input type="hidden" name="_show_episode_modal" value="1">

        <div class="modal-header">
          <h5 class="modal-title" id="episodeModalLabel">New Episode</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
          <div class="row g-3">
            <div class="col-lg-8">
              <div class="mb-3">
                <label class="form-label">Title</label>
                <input name="title" type="text" required
                       class="form-control @error('title') is-invalid @enderror"
                       value="{{ old('title') }}">
                @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>

              <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" rows="5"
                          class="form-control @error('description') is-invalid @enderror">{{ old('description') }}</textarea>
                @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
            </div>

            <div class="col-lg-4">
              <div class="mb-3">
                <label class="form-label">Audio URL</label>
                <input name="audio_url" type="url"
                       placeholder="https://cdn.example.com/episode.mp3"
                       class="form-control @error('audio_url') is-invalid @enderror"
                       value="{{ old('audio_url') }}">
                @error('audio_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>

              <div class="row g-3">
                <div class="col-6">
                  <label class="form-label">Duration (sec)</label>
                  <input name="duration_seconds" type="number" min="0"
                         class="form-control @error('duration_seconds') is-invalid @enderror"
                         value="{{ old('duration_seconds') }}">
                  @error('duration_seconds') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-6">
                  <label class="form-label">Status</label>
                  @php $status = old('status','draft'); @endphp
                  <select name="status" class="form-select @error('status') is-invalid @enderror">
                    <option value="draft"     {{ $status==='draft'?'selected':'' }}>Draft</option>
                    <option value="published" {{ $status==='published'?'selected':'' }}>Published</option>
                  </select>
                  @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
              </div>

              <div class="mt-3">
                <label class="form-label">Publish At (optional)</label>
                <input name="published_at" type="datetime-local"
                       class="form-control @error('published_at') is-invalid @enderror"
                       value="{{ old('published_at') }}">
                @error('published_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-dark">
            <i class="bi bi-plus-lg me-1"></i>Create Episode
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
