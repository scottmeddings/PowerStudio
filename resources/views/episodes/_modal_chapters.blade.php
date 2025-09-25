<div class="modal fade" id="chaptersModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Episode Chapter Markers</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <small class="text-secondary">Add rows, set start time (minutes), then Save.</small>
          <button class="btn btn-outline-primary btn-sm" type="button" id="addChapterRow">
            <i class="bi bi-plus-lg me-1"></i> Add chapter
          </button>
        </div>

        <table class="table align-middle" id="chaptersTable">
          <thead class="table-light">
            <tr>
              <th style="width:60px">#</th>
              <th style="width:140px">Start (minutes)</th>
              <th>Title</th>
              <th style="width:48px"></th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-blush" id="saveChaptersBtn">Save</button>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
(function(){
  const modalEl = document.getElementById('chaptersModal');
  if(!modalEl) return;

  const tbody   = modalEl.querySelector('#chaptersTable tbody');
  const addBtn  = modalEl.querySelector('#addChapterRow');
  const saveBtn = modalEl.querySelector('#saveChaptersBtn');

  let currentEpisodeId = null;

  // --- Helpers ---
  function row(start='', title='', sort=''){
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td class="sort-col text-muted">${sort}</td>
      <td><input class="form-control form-control-sm" placeholder="minutes" value="${start}"></td>
      <td><input class="form-control form-control-sm" placeholder="Chapter title" value="${title}"></td>
      <td class="text-end">
        <button type="button" class="btn btn-outline-danger btn-sm del">
          <i class="bi bi-trash"></i>
        </button>
      </td>`;
    tr.querySelector('.del').onclick = () => {
      tr.remove();
      renumber();
    };
    return tr;
  }

  function msToStr(ms){
    return Math.floor(ms / 60000); // minutes
  }

  function strToMs(minutesStr){
    const minutes = parseInt(minutesStr, 10);
    if (isNaN(minutes)) return null;
    return minutes * 60000;
  }

  function renumber(){
    [...tbody.querySelectorAll('tr')].forEach((tr,i)=>{
      tr.querySelector('.sort-col').textContent = i+1;
    });
  }

  // --- Parse episode ID from URL (/episodes/{id}/...)
  function getEpisodeIdFromUrl() {
    const match = window.location.pathname.match(/episodes\/(\d+)/);
    return match ? match[1] : null;
  }

  // --- Load existing chapters ---
  async function load(episodeId){
    tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">Loading…</td></tr>';
    try {
      const res = await fetch(`/episodes/${episodeId}/chapters`, {
        headers:{'X-Requested-With':'XMLHttpRequest'}
      });
      if(!res.ok) throw new Error(`HTTP ${res.status}`);
      const data = await res.json();
      const items = data.chapters || [];

      tbody.innerHTML = '';
      if(items.length){
        items.forEach((c,i)=>{
          tbody.appendChild(row(msToStr(c.starts_at_ms || 0), c.title || '', i+1));
        });
      } else {
        tbody.appendChild(row('', '', 1));
        tbody.appendChild(row('', '', 2));
      }
    } catch (e) {
      console.error("Failed to load chapters", e);
      tbody.innerHTML = '<tr><td colspan="4" class="text-center text-danger">Failed to load chapters</td></tr>';
    }
  }

  // --- Add chapter ---
  addBtn.onclick = ()=> {
    tbody.appendChild(row('', '', tbody.querySelectorAll('tr').length+1));
  };

  // --- Save chapters ---
  saveBtn.onclick = async ()=>{
    if (!currentEpisodeId) return;

    let chapters = [...tbody.querySelectorAll('tr')].map((tr,i)=>{
      const [startInput,titleInput] = tr.querySelectorAll('input');
      const ms = strToMs(startInput.value.trim());
      return {
        sort: i+1,
        start: startInput.value.trim(),
        starts_at_ms: ms,
        title: titleInput.value.trim()
      };
    }).filter(c=>c.title);

    const invalid = chapters.find(c=>c.starts_at_ms===null);
    if(invalid){
      alert(`Invalid time format: "${invalid.start}". Please enter a number of minutes.`);
      return;
    }

    try {
      const res = await fetch(`/episodes/${currentEpisodeId}/chapters/sync`, {
        method:'POST',
        headers:{
          'Content-Type':'application/json',
          'X-CSRF-TOKEN': @json(csrf_token()),
          'X-Requested-With':'XMLHttpRequest',
        },
        body: JSON.stringify({chapters})
      });
      if(!res.ok) throw new Error(`HTTP ${res.status}`);
      const json = await res.json();
      console.log("Save response:", json);
      bootstrap.Modal.getInstance(modalEl).hide();
    } catch (e) {
      console.error("Failed to save chapters", e);
      alert("Failed to save chapters. Check console for details.");
    }
  };

  // --- Event: open modal ---
  modalEl.addEventListener('show.bs.modal', () => {
    currentEpisodeId = getEpisodeIdFromUrl();
    if (currentEpisodeId) load(currentEpisodeId);
  });

})();
</script>
@endpush
