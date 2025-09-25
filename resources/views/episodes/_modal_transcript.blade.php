<div class="modal fade" id="transcriptModal" data-episode-id="{{ $episode->id }}">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Episode Transcript</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <div id="trEmptyAlert" class="alert alert-info py-2 mb-2 d-none">
          No transcript saved for this episode yet.
        </div>

        <div id="trMeta" class="d-flex justify-content-between small text-muted mb-2 d-none">
          <span id="trFormat"></span>
          <span id="trDuration"></span>
        </div>

        <textarea id="trText" class="form-control" rows="14" name="text"
          placeholder="WEBVTT / SRT / plain text"></textarea>

        <div class="mt-2">
          <input type="file" id="trFile" class="form-control" accept=".vtt,.srt,.txt">
          <small class="text-secondary">Upload .vtt/.srt or edit/paste text above, then Save.</small>
        </div>
      </div>

      <div class="modal-footer">
        <div class="me-auto d-flex align-items-center gap-2">
          <a id="downloadTranscript" class="btn btn-outline-secondary btn-sm disabled" href="#">
            Download
          </a>
          <button type="button" id="deleteTranscript"
            class="btn btn-outline-danger btn-sm" disabled>
            Delete
          </button>
        </div>
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-blush" id="saveTranscript">Save</button>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
(function(){
  const modal = document.getElementById('transcriptModal');
  if (!modal) return;

  const box     = modal.querySelector('#trText');
  const alertEl = modal.querySelector('#trEmptyAlert');
  const metaEl  = modal.querySelector('#trMeta');
  const fmtEl   = modal.querySelector('#trFormat');
  const durEl   = modal.querySelector('#trDuration');
  const dlBtn   = modal.querySelector('#downloadTranscript');
  const delBtn  = modal.querySelector('#deleteTranscript');
  const fileInp = modal.querySelector('#trFile');

  function reflect(body, format, duration_ms, epId) {
    const has = (body || '').trim().length > 0;
    box.value = body || '';
    fmtEl.textContent = 'Format: ' + (format || 'TXT');
    durEl.textContent = duration_ms ? 'Duration: ' + new Date(duration_ms).toISOString().substr(11, 8) : '';

    alertEl.classList.toggle('d-none', has);
    metaEl.classList.toggle('d-none', !has);
    dlBtn.classList.toggle('disabled', !has);
    delBtn.disabled = !has;

    dlBtn.href = has ? `/episodes/${epId}/transcript/download` : '#';
  }

  // Load transcript on modal open
  modal.addEventListener('shown.bs.modal', async () => {
    const epId = modal.dataset.episodeId;
    const resp = await fetch(`/episodes/${epId}/transcript`);
    const data = await resp.json();
    reflect(data.body, data.format, data.duration_ms, epId);
  });

  // Save transcript via AJAX
  modal.querySelector('#saveTranscript').addEventListener('click', async () => {
    const epId = modal.dataset.episodeId;
    const fd = new FormData();
    if (fileInp.files[0]) {
      fd.append('file', fileInp.files[0]);
    } else {
      fd.append('text', box.value);
    }
    const resp = await fetch(`/episodes/${epId}/transcript`, {
      method: 'POST',
      body: fd,
      headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
    });
    const data = await resp.json();
    if (data.ok) {
      const r = await fetch(`/episodes/${epId}/transcript`);
      const d = await r.json();
      reflect(d.body, d.format, d.duration_ms, epId);
    }
  });

  // Delete transcript via AJAX
  delBtn.addEventListener('click', async () => {
    const epId = modal.dataset.episodeId;
    if (!confirm('Delete transcript?')) return;
    const resp = await fetch(`/episodes/${epId}/transcript`, {
      method: 'DELETE',
      headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
    });
    const data = await resp.json();
    if (data.ok) {
      reflect('', 'TXT', null, epId);
    }
  });
})();
</script>
@endpush
