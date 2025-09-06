<div class="modal fade" id="transcriptModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Episode Transcripts</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <div class="mb-2">
          <input type="file" id="trFile" class="form-control" accept=".vtt,.srt,.txt">
          <small class="text-secondary">Upload .vtt/.srt or paste text below.</small>
        </div>
        <textarea id="trText" class="form-control" rows="12" placeholder="WEBVTT / SRT / plain text"></textarea>
      </div>

      <div class="modal-footer">
        <div class="me-auto d-flex align-items-center gap-2">
          <a class="btn btn-outline-secondary btn-sm" id="downloadTranscript" href="#">Download</a>
          <button class="btn btn-outline-danger btn-sm" id="deleteTranscript">Delete</button>
        </div>
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
        <button class="btn btn-blush" id="saveTranscript">Save</button>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
(function(){
  const modalEl = document.getElementById('transcriptModal');
  if(!modalEl) return;

  const trText = modalEl.querySelector('#trText');
  const trFile = modalEl.querySelector('#trFile');
  const saveBtn = modalEl.querySelector('#saveTranscript');
  const delBtn  = modalEl.querySelector('#deleteTranscript');
  const dlBtn   = modalEl.querySelector('#downloadTranscript');

  function load(){
    trText.value = '';
    dlBtn.classList.add('disabled');
    delBtn.classList.add('disabled');
    fetch(@json(route('episodes.transcript.show', $episode)), {headers:{'X-Requested-With':'XMLHttpRequest'}})
      .then(r => r.ok ? r.json() : null)
      .then(data=>{
        if(!data) return;
        if (data.body) trText.value = data.body;
        dlBtn.href = @json(route('episodes.transcript.download', $episode));
        dlBtn.classList.remove('disabled');
        delBtn.classList.remove('disabled');
      });
  }

  saveBtn.onclick = ()=>{
    const form = new FormData();
    if (trFile.files[0]) form.append('file', trFile.files[0]);
    if (trText.value.trim()) form.append('text', trText.value.trim());
    form.append('_token', @json(csrf_token()));

    fetch(@json(route('episodes.transcript.store', $episode)), {
      method:'POST',
      headers:{'X-Requested-With':'XMLHttpRequest'},
      body: form
    }).then(()=> bootstrap.Modal.getInstance(modalEl).hide());
  };

  delBtn.onclick = ()=>{
    fetch(@json(route('episodes.transcript.destroy', $episode)), {
      method:'DELETE',
      headers:{'X-CSRF-TOKEN': @json(csrf_token()), 'X-Requested-With':'XMLHttpRequest'}
    }).then(()=> load());
  };

  modalEl.addEventListener('shown.bs.modal', load);
})();
</script>
@endpush
