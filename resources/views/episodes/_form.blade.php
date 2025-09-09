{{-- resources/views/episodes/_form.blade.php --}}
@php
  /** @var \App\Models\Episode|null $episode */
  $status = old('status', $episode->status ?? 'draft');
@endphp

{{-- Keep status here so the sidebar buttons can change/submit it --}}
<input type="hidden" id="statusField" name="status" value="{{ $status }}">

<div class="row g-3">
  <div class="col-12">
    <label class="form-label">Title</label>
    <input name="title" type="text" required
           class="form-control @error('title') is-invalid @enderror"
           value="{{ old('title', $episode->title ?? '') }}">
    @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
  </div>

  <div class="col-12">
    <label class="form-label">Description</label>
    <textarea name="description" rows="6"
              class="form-control @error('description') is-invalid @enderror">{{ old('description', $episode->description ?? '') }}</textarea>
    @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
  </div>

  <div class="col-md-6">
    <label class="form-label">Upload Audio</label>
    <input type="file" name="audio" accept="audio/*"
           class="form-control @error('audio') is-invalid @enderror">
    @error('audio') <div class="invalid-feedback">{{ $message }}</div> @enderror
    <small class="text-secondary d-block mt-1">If you also provide a URL, the uploaded file will be used.</small>
  </div>

  <div class="col-md-6">
    <label class="form-label">Audio URL</label>
    <input name="audio_url" type="url"
           class="form-control @error('audio_url') is-invalid @enderror"
           value="{{ old('audio_url', $episode->audio_url ?? '') }}"
           placeholder="https://cdn.example.com/episode.mp3">
    @error('audio_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
  </div>

  <div class="col-md-4">
    <label class="form-label">Status</label>
    <select name="status" id="statusSelect" class="form-select @error('status') is-invalid @enderror">
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
           value="{{ old('published_at', optional($episode->published_at)->format('Y-m-d\TH:i')) }}">
    @error('published_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
  </div>
</div>
