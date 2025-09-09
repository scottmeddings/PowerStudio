@php
  // Ensure the relation is present even if controller forgot to eager-load
  $episode->loadMissing('transcript');

  $tr   = $episode->transcript;                       // App\Models\EpisodeTranscript|null
  $body = (string) data_get($tr, 'body', '');
  $has  = mb_strlen(trim($body)) > 0;
@endphp

<div class="modal fade" id="transcriptModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Episode Transcript</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        @if($has)
          <div class="d-flex justify-content-between small text-muted mb-2">
            <span>Format: {{ strtoupper($tr->format ?? 'txt') }}</span>
            @if(!empty($tr?->duration_ms))
              <span>Duration: {{ gmdate('H:i:s', (int) ($tr->duration_ms/1000)) }}</span>
            @endif
          </div>
        @else
          <div class="alert alert-info py-2 mb-2">No transcript saved for this episode yet.</div>
        @endif

        {{-- Prefilled directly from DB --}}
        <textarea
          id="trText"
          class="form-control"
          rows="14"
          name="text"
          form="saveTranscriptForm"
          placeholder="WEBVTT / SRT / plain text"
        >{{ old('text', $body) }}</textarea>

        <div class="mt-2">
          <input type="file" id="trFile" class="form-control" accept=".vtt,.srt,.txt"
                 name="file" form="saveTranscriptForm">
          <small class="text-secondary">Upload .vtt/.srt or edit/paste text above, then Save.</small>
        </div>
      </div>

      <div class="modal-footer">
        <div class="me-auto d-flex align-items-center gap-2">
          <a id="downloadTranscript"
             class="btn btn-outline-secondary btn-sm {{ $has ? '' : 'disabled' }}"
             href="{{ $has ? route('episodes.transcript.download', $episode) : '#' }}">Download</a>

          <form id="deleteTranscriptForm" method="POST"
                action="{{ route('episodes.transcript.destroy', $episode) }}">
            @csrf @method('DELETE')
            <button type="submit" id="deleteTranscript"
                    class="btn btn-outline-danger btn-sm" {{ $has ? '' : 'disabled' }}>
              Delete
            </button>
          </form>
        </div>

        <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>

        <form id="saveTranscriptForm" method="POST"
              action="{{ route('episodes.transcript.store', $episode) }}"
              enctype="multipart/form-data">
          @csrf
          <button type="submit" class="btn btn-blush" id="saveTranscript">Save</button>
        </form>
      </div>
    </div>
  </div>
</div>
