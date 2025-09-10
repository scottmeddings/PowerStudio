{{-- resources/views/episodes/_modal_transcript.blade.php --}}
@php
  use Illuminate\Support\Facades\Storage;

  $episode->loadMissing('transcript');
  $tr = $episode->transcript;

  // Server-side prefill (DB body or file on disk)
  $body = '';
  if ($tr) {
      $body = (string) ($tr->body ?? '');
      if ($body === '' && $tr->storage_path) {
          try {
              $disk = Storage::disk('public'); // adjust disk if different
              if ($disk->exists($tr->storage_path)) {
                  $body = (string) $disk->get($tr->storage_path);
              }
          } catch (\Throwable $e) { /* ignore */ }
      }
  }
  // Prefer old('text') only if it exists in session (e.g., validation error)
  $prefill = old('text', null);
  if ($prefill === null) $prefill = $body;

  $has = trim((string)$prefill) !== '';
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
            <span>Format: {{ strtoupper($tr->format ?? 'TXT') }}</span>
            @if(!empty($tr?->duration_ms))
              <span>Duration: {{ gmdate('H:i:s', (int) ($tr->duration_ms/1000)) }}</span>
            @endif
          </div>
        @else
          <div class="alert alert-info py-2 mb-2">No transcript saved for this episode yet.</div>
        @endif

        <textarea
          id="trText"
          class="form-control"
          rows="14"
          name="text"
          form="saveTranscriptForm"
          data-fetch-url="{{ route('episodes.transcript.show', $episode) }}"
          placeholder="WEBVTT / SRT / plain text"
        >{{ $prefill }}</textarea>

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

@push('scripts')
<script>
(function(){
  const modal   = document.getElementById('transcriptModal');
  if (!modal) return;

  const box     = modal.querySelector('#trText');
  const fetchUrl= box?.dataset.fetchUrl;

  // Try to fetch transcript when the modal opens and inject into the textarea.
  modal.addEventListener('show.bs.modal', async () => {
    if (!box || !fetchUrl) return;

    // If already filled (server prefill), don’t refetch unless empty.
    if ((box.value || '').trim()) return;

    try {
      const res = await fetch(fetchUrl, {
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json, text/plain;q=0.8, */*;q=0.5'
        },
        credentials: 'same-origin',
        cache: 'no-store'
      });

      let text = '';
      const ct = (res.headers.get('Content-Type') || '').toLowerCase();
      if (ct.includes('application/json')) {
        const j = await res.json();
        // Accept common shapes: {body: "..."} or {data:{body:"..."}}
        text = (j && (j.body || (j.data && j.data.body))) || '';
      } else {
        text = await res.text();
        // If the endpoint returns the entire JSON as text, try to parse
        try {
          const j = JSON.parse(text);
          text = (j && (j.body || (j.data && j.data.body))) || text;
        } catch(_) {}
      }

      // Normalize to UTF-8-ish display in the textarea
      // (browsers already expect UTF-8; just ensure it's a string)
      if (typeof text !== 'string') text = String(text ?? '');

      box.value = text;
    } catch (e) {
      // Leave whatever is there; no hard failure
      // console.debug('Transcript fetch failed', e);
    }
  });

})();
</script>
@endpush
