<div class="modal fade" id="chaptersModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Episode Chapter Markers</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <small class="text-secondary">Add rows, set start time, then Save.</small>
          <button class="btn btn-outline-primary btn-sm" type="button" id="addChapterRow">
            <i class="bi bi-plus-lg me-1"></i> Add chapter
          </button>
        </div>

        <table class="table align-middle" id="chaptersTable">
          <thead class="table-light">
            <tr>
              <th style="width:140px">Start (mm:ss)</th>
              <th>Title</th>
              <th style="width:48px"></th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>

      <div class="modal-footer">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-blush" id="saveChaptersBtn">Save</button>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
(function(){
  const modalEl = document.getElementById('chaptersModal');
  if(!modalEl) return;

  const tbody = modalEl.querySelector('#chaptersTable tbody');
  const addBtn = modalEl.querySelector('#addChapterRow');
  const saveBtn = modalEl.querySelector('#saveChaptersBtn');
  const EPISODE_ID = @json($episode->id);

  function row(start='', title=''){
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td><input class="form-control form-control-sm" placeholder="00:00" value="${start}"></td>
      <td><input class="form-control form-control-sm" placeholder="Chapter title" value="${title}"></td>
      <td class="text-end">
        <button class="btn btn-outline-danger btn-sm del"><i class="bi bi-trash"></i></button>
      </td>`;
    tr.querySelector('.del').onclick = () => tr.remove();
    return tr;
  }

  function load(){
    tbody.innerHTML = '';
    fetch(@json(route('episodes.chapters.index', $episode)), {headers:{'X-Requested-With':'XMLHttpRequest'}})
      .then(r=>r.json())
      .then(items=>{
        (items.length ? items : [{},{}]).forEach(c=>{
          tbody.appendChild(row(msToStr(c.starts_at_ms || 0), c.title || ''));
        });
      });
  }

  function msToStr(ms){
    const s = Math.round(ms/1000);
    const m = Math.floor(s/60), sec = s%60;
    return String(m).padStart(2,'0')+':'+String(sec).padStart(2,'0');
  }

  addBtn.onclick = ()=> tbody.appendChild(row());

  saveBtn.onclick = ()=>{
    const chapters = [...tbody.querySelectorAll('tr')].map(tr=>{
      const [startInput,titleInput] = tr.querySelectorAll('input');
      return { start: startInput.value.trim(), title: titleInput.value.trim() };
    }).filter(c=>c.title);

    fetch(@json(route('episodes.chapters.sync', $episode)), {
      method:'POST',
      headers:{
        'Content-Type':'application/json',
        'X-CSRF-TOKEN': @json(csrf_token()),
        'X-Requested-With':'XMLHttpRequest',
      },
      body: JSON.stringify({chapters})
    }).then(r=>r.json()).then(()=>{
      bootstrap.Modal.getInstance(modalEl).hide();
    });
  };

  modalEl.addEventListener('shown.bs.modal', load);
})();
</script>
@endpush
