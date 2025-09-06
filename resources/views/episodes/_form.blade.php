@php
  /** @var \App\Models\Episode|null $episode */
  $editing = isset($episode);
  $status  = old('status', $episode->status ?? 'draft');
@endphp

<div class="row g-3">
  {{-- LEFT: fields --}}
  <div class="col-lg-8">
    {{-- Title --}}
    <div class="mb-3">
      <label class="form-label">Title</label>
      <input name="title" type="text" required
             class="form-control @error('title') is-invalid @enderror"
             value="{{ old('title', $episode->title ?? '') }}">
      @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    {{-- Description --}}
    <div class="mb-3">
      <label class="form-label">Description</label>
      <textarea name="description" rows="6"
                class="form-control @error('description') is-invalid @enderror">{{ old('description', $episode->description ?? '') }}</textarea>
      @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    {{-- Audio upload + URL --}}
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Upload Audio (optional)</label>
        <input type="file" name="audio" accept="audio/*"
               class="form-control @error('audio') is-invalid @enderror">
        @error('audio') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <small class="text-secondary d-block mt-1">If you also provide a URL, the uploaded file will be used.</small>
      </div>

      <div class="col-md-6">
        <label class="form-label">Audio URL (optional)</label>
        <input name="audio_url" type="url"
               class="form-control @error('audio_url') is-invalid @enderror"
               value="{{ old('audio_url', $episode->audio_url ?? '') }}"
               placeholder="https://cdn.example.com/episode.mp3">
        @error('audio_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>
    </div>

    {{-- Status + duration --}}
    <div class="row g-3 mt-1">
      <div class="col-md-4">
        <label class="form-label">Status</label>
        <select id="statusSelect" name="status" class="form-select @error('status') is-invalid @enderror">
          <option value="draft"     {{ $status === 'draft' ? 'selected' : '' }}>Draft</option>
          <option value="published" {{ $status === 'published' ? 'selected' : '' }}>Published</option>
        </select>
        @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      <div class="col-md-4">
        <label class="form-label">Duration (sec)</label>
        <input name="duration_seconds" type="number" min="0"
               class="form-control @error('duration_seconds') is-invalid @enderror"
               value="{{ old('duration_seconds', $episode->duration_seconds ?? '') }}">
        @error('duration_seconds') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      <div class="col-md-4">
        <label class="form-label">Publish At (optional)</label>
        <input name="published_at" type="datetime-local"
               class="form-control @error('published_at') is-invalid @enderror"
               value="{{ old('published_at', isset($episode->published_at) ? $episode->published_at->format('Y-m-d\TH:i') : '') }}">
        @error('published_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>
    </div>
  </div>

  {{-- RIGHT: actions (NO nested forms!) --}}
  <div class="col-lg-4">
    <div class="border rounded p-3 bg-light">
      <h6 class="mb-3">Actions</h6>

      <div class="d-grid gap-2">
        {{-- Save / Update (submits the main form that wraps this partial) --}}
        <button class="btn btn-blush" type="submit">
          <i class="bi bi-save me-1"></i>{{ $editing ? 'Save Changes' : 'Create Episode' }}
        </button>

        {{-- Save as draft (forces status=draft, then submits main form) --}}
        <button id="saveAsDraftBtn" class="btn btn-outline-secondary" type="button">
          Save as draft
        </button>

        {{-- Publish / Unpublish use external forms via form="" attribute --}}
        @if($editing)
          @if(strtolower($status) !== 'published')
            <button type="submit" form="publishForm" class="btn btn-success">
              <i class="bi bi-megaphone me-1"></i>Publish now
            </button>
          @else
            <button type="submit" form="unpublishForm" class="btn btn-outline-warning">
              <i class="bi bi-arrow-counterclockwise me-1"></i>Unpublish
            </button>
          @endif
        @endif

        {{-- Cancel --}}
        <a class="btn btn-outline-secondary" href="{{ route('episodes') }}">Cancel</a>

        {{-- Delete uses external hidden form too (avoid nesting) --}}
        @if($editing)
          <button type="submit"
                  form="deleteEpisodeForm"
                  class="btn btn-outline-danger"
                  onclick="return confirm('Delete this episode?');">
            <i class="bi bi-trash me-1"></i>Delete
          </button>
        @endif
      </div>
    </div>
  </div>
</div>

{{-- tiny helper for "Save as draft" --}}
<script>
  (function () {
    const btn = document.getElementById('saveAsDraftBtn');
    const status = document.getElementById('statusSelect');
    if (btn && status) {
      btn.addEventListener('click', () => {
        status.value = 'draft';
        // submit the nearest wrapping form (the main update/create form)
        btn.closest('form').submit();
      });
    }
  })();
</script>
