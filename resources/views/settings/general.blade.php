{{-- resources/views/settings/general.blade.php --}}
@extends('layouts.app')

@section('title','Settings · General')
@section('page-title','General')

@section('content')
  <div class="d-flex align-items-center gap-2 mb-3"></div>

  <div class="section-card p-4">
    @if(session('ok')) <div class="alert alert-success">{{ session('ok') }}</div> @endif

    <form method="POST" action="{{ route('settings.general.update') }}" class="row g-4" enctype="multipart/form-data">
      @csrf

      {{-- Podcast Title --}}
      <div class="col-12">
          <label class="form-label fw-semibold">Podcast Title <span class="text-danger">*</span></label>
          <input
          name="title"
          class="form-control @error('title') is-invalid @enderror"
          value="{{ old('title', $title ?? 'MyPodcast') }}"
          required
        >
        </div>

      {{-- Brief Description --}}
      <div class="col-12">
        <label class="form-label fw-semibold">Brief Description <span class="text-danger">*</span></label>

        {{-- lightweight toolbar --}}
        <div class="d-flex gap-2 mb-2">
          <button type="button" class="btn btn-sm btn-outline-secondary" data-cmd="bold"><i class="bi bi-type-bold"></i></button>
          <button type="button" class="btn btn-sm btn-outline-secondary" data-cmd="italic"><i class="bi bi-type-italic"></i></button>
          <button type="button" class="btn btn-sm btn-outline-secondary" data-cmd="ul"><i class="bi bi-list-ul"></i></button>
          <button type="button" class="btn btn-sm btn-outline-secondary" data-cmd="ol"><i class="bi bi-list-ol"></i></button>
        </div>

        <textarea id="desc" name="description" rows="8"
                  class="form-control @error('description') is-invalid @enderror"
                  placeholder="Tell listeners what your podcast is about…">{{ old('description',$description) }}</textarea>
        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      {{-- Podcast Category --}}
      <div class="col-md-6">
        <label class="form-label fw-semibold">Podcast Category <span class="text-danger">*</span></label>
        <div class="input-group">
          <select name="category" class="form-select @error('category') is-invalid @enderror">
            @php $opts = ['Technology','Business','Education','News','Arts','Health & Fitness','Leisure']; @endphp
            @foreach($opts as $opt)
              <option value="{{ $opt }}" @selected(old('category',$category)===$opt)>{{ $opt }}</option>
            @endforeach
          </select>
          <button class="btn btn-outline-secondary" type="button" title="Add subcategory (coming soon)" disabled>
            <i class="bi bi-plus-lg"></i>
          </button>
          @error('category') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        </div>
        <div class="form-text">Apple Podcasts only recognizes the first category & subcategory.</div>
      </div>

      {{-- More Options (collapsible) --}}
      <div class="col-12">
        <button class="btn btn-link p-0" type="button" data-bs-toggle="collapse" data-bs-target="#moreOptions" aria-expanded="false">
          <span class="me-1">More Options</span><i class="bi bi-caret-down-fill"></i>
        </button>

        <div id="moreOptions" class="collapse mt-3">
          <div class="row g-3">

           
           {{-- Podcast Website (subdomain on powerpod.com) --}}
            <div class="col-md-6">
              <label class="form-label fw-semibold">Podcast Website</label>
              <div class="input-group">
                <span class="input-group-text">https://</span>
                <input
                  name="site_subdomain"
                  class="form-control @error('site_subdomain') is-invalid @enderror"
                  placeholder="yourshow"
                  value="{{ old('site_subdomain', $subdomain) }}"
                  pattern="[A-Za-z0-9][A-Za-z0-9-]{0,61}[A-Za-z0-9]"
                  inputmode="latin"
                  autocomplete="off"
                  spellcheck="false"
                >
                <span class="input-group-text">.powerpod.com</span>
                @error('site_subdomain') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
              </div>
              <div class="form-text">
                Changing your podcast website also changes the feed URL (it becomes <code>https://&lt;subdomain&gt;.powerpod.com/feed.xml</code>).
              </div>
            </div>


            {{-- Language --}}
            <div class="col-md-6">
              <label class="form-label fw-semibold">Language</label>
              @php $langValue = old('language',$language ?? 'en-us'); @endphp
              <select name="language" class="form-select @error('language') is-invalid @enderror">
                @foreach([
                  'en-us' => 'English (US) [en-US]',
                  'en-AU' => 'English (Australia) [en-AU]',
                  'en-GB' => 'English (UK) [en-GB]',
                  'en'    => 'English [en]',
                  'hi'    => 'Hindi [hi]',
                  'zh'    => 'Chinese [zh]',
                ] as $code => $label)
                  <option value="{{ $code }}" @selected(strtolower($langValue)===strtolower($code))>{{ $label }}</option>
                @endforeach
              </select>
              @error('language') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            </div>

            {{-- Country of Origin --}}
            <div class="col-md-6">
              <label class="form-label fw-semibold">Country of Origin</label>
              @php $countryValue = old('country',$country ?? 'Global'); @endphp
              <select name="country" class="form-select @error('country') is-invalid @enderror">
                @foreach(['Global','Australia','United States','United Kingdom','India'] as $c)
                  <option value="{{ $c }}" @selected($countryValue===$c)>{{ $c }}</option>
                @endforeach
              </select>
              @error('country') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            </div>

            {{-- Timezone --}}
            <div class="col-md-6">
              <label class="form-label fw-semibold">Timezone</label>
              @php $tzValue = old('timezone',$timezone ?? config('app.timezone','UTC')); @endphp
              <select name="timezone" class="form-select @error('timezone') is-invalid @enderror">
                @foreach(['Australia/Melbourne','Australia/Sydney','UTC','America/Los_Angeles','Europe/London'] as $tz)
                  <option value="{{ $tz }}" @selected($tzValue===$tz)>{{ $tz }}</option>
                @endforeach
              </select>
              @error('timezone') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
              <div class="form-text">Current time: {{ now()->format('M d, Y, h:i A') }}</div>
            </div>

            {{-- Podcast Type --}}
            <div class="col-md-6">
              <label class="form-label fw-semibold d-block">Podcast Type</label>
              @php $ptype = old('podcast_type',$podcast_type ?? 'episodic'); @endphp
              <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="podcast_type" id="type_ep" value="episodic" @checked($ptype==='episodic')>
                <label class="form-check-label" for="type_ep">Episodic</label>
              </div>
              <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="podcast_type" id="type_serial" value="serial" @checked($ptype==='serial')>
                <label class="form-check-label" for="type_serial">Serial</label>
              </div>
              @error('podcast_type') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
            </div>

            {{-- Episode Download Link visibility --}}
            <div class="col-md-6">
              <label class="form-label fw-semibold">Episode Download Link</label>
              @php $dlv = old('download_visibility',$download_visibility ?? 'hidden'); @endphp
              <select name="download_visibility" class="form-select @error('download_visibility') is-invalid @enderror">
                <option value="hidden" @selected($dlv==='hidden')>Hidden</option>
                <option value="public" @selected($dlv==='public')>Public</option>
              </select>
              @error('download_visibility') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            </div>

            {{-- Podcast Site Top Bar --}}
            <div class="col-md-6">
              <label class="form-label fw-semibold">Podcast Site Top Bar</label>
              @php $topbar = old('site_top_bar',$site_top_bar ?? 'show'); @endphp
              <select name="site_top_bar" class="form-select @error('site_top_bar') is-invalid @enderror">
                <option value="show" @selected($topbar==='show')>Show</option>
                <option value="hide" @selected($topbar==='hide')>Hide</option>
              </select>
              @error('site_top_bar') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            </div>

          </div>
        </div>
      </div>

      <div class="col-12">
        <button class="btn btn-blush px-4">Update Options</button>
      </div>
    </form>
  </div>
  {{-- Settings · Collaborators --}}
<div class="section-card p-4 mt-4">
  <h5 class="mb-3">Collaborators</h5>
  <p class="text-muted">Invite teammates to access all Episodes and Settings (admin-level by default).</p>

  <form class="row g-3" method="POST" action="{{ route('collab.invite') }}">
    @csrf
    <div class="col-md-6">
      <label class="form-label fw-semibold">Email *</label>
      <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
             placeholder="teammate@example.com" required>
      @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3">
      <label class="form-label fw-semibold">Role</label>
      <select name="role" class="form-select">
        <option value="admin" selected>Admin (full access)</option>
        <option value="viewer">Viewer (read-only)</option>
      </select>
    </div>
    <div class="col-md-3 d-flex align-items-end">
      <button class="btn btn-primary w-100">Send Invite</button>
    </div>
  </form>

  @php
    $collabs = \App\Models\Collaborator::orderByRaw('accepted_at IS NULL DESC')->orderBy('email')->get();
  @endphp

  <div class="table-responsive mt-4">
    <table class="table align-middle">
      <thead>
        <tr>
          <th>Email</th><th>Status</th><th>Role</th><th>Invited By</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($collabs as $c)
          <tr>
            <td>{{ $c->email }}</td>
            <td>
              @if($c->accepted_at)
                <span class="badge bg-success">Accepted</span>
              @else
                <span class="badge bg-warning text-dark">Pending</span>
              @endif
            </td>
            <td>{{ ucfirst($c->role) }}</td>
            <td>{{ optional($c->inviter)->name ?? '—' }}</td>
            <td class="text-nowrap">
              @if(!$c->accepted_at)
                <a href="{{ route('collab.accept',['token'=>$c->token]) }}" class="btn btn-sm btn-outline-secondary">Copy Invite Link</a>
              @endif
              <form method="POST" action="{{ route('collab.revoke',$c->id) }}" class="d-inline">
                @csrf
                <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Revoke access?')">Revoke</button>
              </form>
            </td>
          </tr>
        @empty
          <tr><td colspan="5" class="text-muted">No collaborators yet.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>


  @push('scripts')
  <script>
    // lightweight toolbar for description
    (function () {
      const area = document.getElementById('desc');
      if (!area) return;

      const toMarkdown = (cmd) => {
        const start = area.selectionStart, end = area.selectionEnd;
        const val = area.value;
        const selected = val.substring(start, end);

        let replaced = selected;
        if (cmd === 'bold')   replaced = `**${selected || 'bold text'}**`;
        if (cmd === 'italic') replaced = `*${selected || 'italic text'}*`;
        if (cmd === 'ul')     replaced = selected.split('\n').map(l => l ? `- ${l}` : '').join('\n');
        if (cmd === 'ol')     replaced = selected.split('\n').map((l,i) => l ? `${i+1}. ${l}` : '').join('\n');

        area.value = val.slice(0, start) + replaced + val.slice(end);
        area.focus();
        area.selectionStart = start;
        area.selectionEnd   = start + replaced.length;
      };

      document.querySelectorAll('[data-cmd]').forEach(btn => {
        btn.addEventListener('click', () => toMarkdown(btn.dataset.cmd));
      });
    })();
  </script>
  @endpush
@endsection
