@php
  /** @var \App\Models\Episode|null $episode */
  $editing = isset($episode);
@endphp

<div class="row g-3">
  <div class="col-lg-8">
    <div class="mb-3">
      <label class="form-label">Title</label>
      <input name="title" type="text" required
             class="form-control @error('title') is-invalid @enderror"
             value="{{ old('title', $episode->title ?? '') }}">
      @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="mb-3">
      <label class="form-label">Description</label>
      <textarea name="description" rows="6"
                class="form-control @error('description') is-invalid @enderror">{{ old('description', $episode->description ?? '') }}</textarea>
      @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Audio URL</label>
        <input name="audio_url" type="url"
               class="form-control @error('audio_url') is-invalid @enderror"
               value="{{ old('audio_url', $episode->audio_url ?? '') }}"
               placeholder="https://cdn.example.com/episode.mp3">
        @error('audio_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>
      <div class="col-md-3">
        <label class="form-label">Duration (sec)</label>
        <input name="duration_seconds" type="number" min="0"
               class="form-control @error('duration_seconds') is-invalid @enderror"
               value="{{ old('duration_seconds', $episode->duration_seconds ?? '') }}">
        @error('duration_seconds') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>
      <div class="col-md-3">
        <label class="form-label">Status</label>
        <select name="status" class="form-select @error('status') is-invalid @enderror">
          @php $status = old('status', $episode->status ?? 'draft'); @endphp
          <option value="draft"     {{ $status === 'draft' ? 'selected' : '' }}>Draft</option>
          <option value="published" {{ $status === 'published' ? 'selected' : '' }}>Published</option>
        </select>
        @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>
    </div>

    <div class="mt-3">
      <label class="form-label">Publish At (optional)</label>
      <input name="published_at" type="datetime-local"
             class="form-control @error('published_at') is-invalid @enderror"
             value="{{ old('published_at', isset($episode->published_at) ? $episode->published_at->format('Y-m-d\TH:i') : '') }}">
      @error('published_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
  </div>

  <div class="col-lg-4">
    <div class="border rounded p-3 bg-light">
      <h6 class="mb-3">Actions</h6>
      <div class="d-grid gap-2">
        <button class="btn btn-dark" type="submit">
          <i class="bi bi-save me-1"></i>{{ $editing ? 'Save Changes' : 'Create Episode' }}
        </button>
        <a class="btn btn-outline-secondary" href="{{ route('episodes') }}">Cancel</a>
        @if($editing)
          <form method="POST" action="{{ route('episodes.destroy', $episode) }}"
                onsubmit="return confirm('Delete this episode?');">
            @csrf @method('DELETE')
            <button class="btn btn-outline-danger w-100" type="submit">
              <i class="bi bi-trash me-1"></i>Delete
            </button>
          </form>
        @endif
      </div>
    </div>
  </div>
</div>
