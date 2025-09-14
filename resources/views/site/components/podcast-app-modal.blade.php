

{{-- resources/views/components/podcast-app-modal.blade.php --}}
@props([
  'provider' => null,     // e.g. 'apple'
  'display'  => null,     // e.g. 'Apple Podcasts'
  'app'      => null,     // \App\Models\PodcastApp|null
  'rss'      => url('/feed/podcast.xml'),
])

<div class="modal fade" id="podcastAppModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <form id="podcastAppForm" method="POST">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title">
            <span id="pam-title">{{ $display ?? 'Podcast App' }}</span>
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
          {{-- Your RSS feed (read-only helper) --}}
          <div class="mb-3">
            <label class="form-label">Your RSS feed</label>
            <div class="input-group">
              <input type="text" class="form-control" value="{{ $rss }}" readonly>
              <button class="btn btn-outline-secondary" type="button"
                      onclick="navigator.clipboard?.writeText('{{ $rss }}')">Copy</button>
            </div>
          </div>

          {{-- External show URL in the directory --}}
          <div class="mb-2">
            <label class="form-label">Your podcast URL in <span id="pam-display">{{ $display ?? '' }}</span></label>
            <input type="url" name="external_url" id="pam-url" class="form-control"
                   placeholder="https://.../your-show"
                   value="">
            <div class="form-text">Paste the public show URL from the directory after you submit.</div>
          </div>

          {{-- Provider specific settings (optional) --}}
          <div id="pam-config" class="d-none">
            {{-- Example of a provider specific option:
            <div class="mt-3">
              <label class="form-label">Region</label>
              <select class="form-select" name="config[region]">
                <option value="">Auto</option>
                <option value="AU">Australia</option>
                <option value="US">United States</option>
              </select>
            </div>
            --}}
          </div>
        </div>

        <div class="modal-footer">
          <input type="hidden" name="action" id="pam-action" value="save">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-dark">Save</button>
          <button type="submit" class="btn btn-primary"
                  onclick="document.getElementById('pam-action').value='submit'">Submit</button>
          <button type="button" class="btn btn-outline-danger ms-auto" id="pam-delete">Clear</button>
        </div>
      </form>
    </div>
  </div>
</div>

@push('scripts')
<script>
(function(){
  const modal = document.getElementById('podcastAppModal');
  const form  = document.getElementById('podcastAppForm');
  const title = document.getElementById('pam-title');
  const disp  = document.getElementById('pam-display');
  const url   = document.getElementById('pam-url');
  const del   = document.getElementById('pam-delete');

  // Open with data-* passed from the Manage/Submit buttons
  modal.addEventListener('show.bs.modal', function (ev) {
    const btn      = ev.relatedTarget;
    const provider = btn?.dataset.provider;
    const display  = btn?.dataset.display || 'Podcast App';
    const action   = btn?.dataset.action || 'save';
    const upsert   = btn?.dataset.upsert;   // form action for POST
    const destroy  = btn?.dataset.destroy;  // delete endpoint
    const current  = btn?.dataset.url || '';

    title.textContent = display;
    disp.textContent  = display;
    url.value         = current;

    form.action = upsert;
    del.onclick = function(){
      if(!destroy) return;
      if(!confirm('Clear configuration for '+display+'?')) return;
      const f = document.createElement('form');
      f.method = 'POST';
      f.action = destroy;
      f.innerHTML = '@csrf @method("DELETE")';
      document.body.appendChild(f); f.submit();
    };
  });
})();
</script>
@endpush
