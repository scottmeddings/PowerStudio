@extends('layouts.app')

@section('title','Settings · Feed')
@section('page-title','Feed')

@section('content')
  <div class="section-card p-4">
    @if(session('ok')) <div class="alert alert-success">{{ session('ok') }}</div> @endif

    <form method="POST" action="{{ route('settings.feed.update') }}" class="row g-4">
      @csrf

      {{-- ===== Podcast Feed Settings ===== --}}
      <div class="col-12">
        <h5 class="mb-3">Podcast Feed Settings</h5>

        {{-- Feed URL (read-only, auto-generated) --}}
        <div class="mb-3">
          <label class="form-label fw-semibold">Podcast Feed URL</label>
          <div class="input-group">
            <input
              type="text"
              class="form-control"
              value="{{ $feed_url }}"
              readonly
              aria-readonly="true"
              spellcheck="false"
              autocomplete="off"
              inputmode="none"
              onfocus="this.select()"
            >
            <button type="button" class="btn btn-outline-secondary" id="copyFeedUrl">Copy</button>
          </div>
          <div class="form-text">
            This URL is auto-generated. It’s
            <code>{{ rtrim(old('site_url',$site_url ?: config('app.url')), '/') }}</code><code>/feed.xml</code>
            when a Podcast Website is set; otherwise it’s your app base URL + <code>/&lt;podcast-slug&gt;/feed.xml</code>.
          </div>
        </div>

        {{-- Explicit --}}
        <div class="mb-3">
          <label class="form-label fw-semibold me-3">Content Explicit</label>
          <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="explicit" id="exp_true" value="1"
                   @checked(old('explicit', $explicit ? '1':'0') === '1')>
            <label class="form-check-label" for="exp_true">Explicit/True</label>
          </div>
          <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="explicit" id="exp_false" value="0"
                   @checked(old('explicit', $explicit ? '1':'0') === '0')>
            <label class="form-check-label" for="exp_false">Clean/False</label>
          </div>
          @error('explicit') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
        </div>

        {{-- Apple Summary --}}
        <div>
          <label class="form-label fw-semibold">Apple Podcasts Summary</label>
          <textarea name="apple_summary" rows="6" class="form-control @error('apple_summary') is-invalid @enderror">{{ old('apple_summary',$apple_summary) }}</textarea>
          @error('apple_summary') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
      </div>

      {{-- ===== Advanced Feed Settings ===== --}}
      <div class="col-12">
        <hr class="my-4">
        <button class="btn btn-link p-0" type="button" data-bs-toggle="collapse" data-bs-target="#advancedFeed" aria-expanded="true">
          <span class="me-1">Advanced Feed Settings</span><i class="bi bi-caret-down-fill"></i>
        </button>

        <div id="advancedFeed" class="collapse show mt-3">
          <div class="row g-3">

            {{-- Podcast Website --}}
            <div class="col-md-6">
              <label class="form-label fw-semibold">Podcast Website</label>
              <input type="url" name="site_url" class="form-control @error('site_url') is-invalid @enderror"
                     value="{{ old('site_url',$site_url) }}" placeholder="https://example.com">
              @error('site_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            {{-- Episode Link --}}
            <div class="col-md-6">
              <label class="form-label fw-semibold">Episode Link</label>
              <select name="episode_link" class="form-select @error('episode_link') is-invalid @enderror">
                @foreach(['podpower' => 'Podpower Episode Link','external' => 'Original media file URL'] as $k => $v)
                  <option value="{{ $k }}" @selected(old('episode_link',$episode_link)===$k)>{{ $v }}</option>
                @endforeach
              </select>
              @error('episode_link') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            </div>

            {{-- Episode Number Limit --}}
            <div class="col-md-6">
              <label class="form-label fw-semibold">Episode Number Limit</label>
              <input type="number" min="1" max="1000" name="episode_number_limit"
                     class="form-control @error('episode_number_limit') is-invalid @enderror"
                     value="{{ old('episode_number_limit',$episode_number_limit) }}">
              @error('episode_number_limit') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            {{-- Episode Artwork Tag --}}
            <div class="col-md-6">
              <label class="form-label fw-semibold">Episode Artwork Tag</label>
              <select name="episode_artwork_tag" class="form-select @error('episode_artwork_tag') is-invalid @enderror">
                @foreach(['itunes' => 'Use iTunes image tag','episode' => 'Use per-episode image'] as $k => $v)
                  <option value="{{ $k }}" @selected(old('episode_artwork_tag',$episode_artwork_tag)===$k)>{{ $v }}</option>
                @endforeach
              </select>
              @error('episode_artwork_tag') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            </div>

            {{-- Ownership verification EMAIL (string) --}}
            <div class="col-md-6">
              <label class="form-label fw-semibold">Ownership Verification Email</label>
              <input type="email" name="ownership_verification_email"
                     class="form-control @error('ownership_verification_email') is-invalid @enderror"
                     value="{{ old('ownership_verification_email', $ownership_verification_email) }}"
                     placeholder="your@email.com">
              @error('ownership_verification_email') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            {{-- Switches --}}
            <div class="col-md-6 d-flex align-items-center justify-content-between">
              <label class="form-label fw-semibold mb-0">Apple Podcasts Verification</label>
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="sw_apv" name="apple_podcasts_verification" value="1"
                       @checked(old('apple_podcasts_verification', $apple_podcasts_verification))>
              </div>
            </div>

            <div class="col-md-6 d-flex align-items-center justify-content-between">
              <label class="form-label fw-semibold mb-0">Remove from Apple Directory</label>
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="sw_rm" name="remove_from_apple_directory" value="1"
                       @checked(old('remove_from_apple_directory', $remove_from_apple_directory))>
              </div>
            </div>

            {{-- Redirect to new feed --}}
            <div class="col-md-12">
              <label class="form-label fw-semibold">Redirect to a New Feed</label>
              <input type="url" name="redirect_to_new_feed" class="form-control @error('redirect_to_new_feed') is-invalid @enderror"
                     value="{{ old('redirect_to_new_feed',$redirect_to_new_feed) }}"
                     placeholder="https://example.com/new-feed.xml">
              @error('redirect_to_new_feed') <div class="invalid-feedback">{{ $message }}</div> @enderror
              <div class="form-text">If set, readers will be redirected to the new feed URL.</div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-12">
        <button class="btn btn-blush px-4">Update Options</button>
      </div>
    </form>
  </div>

  @push('scripts')
  <script>
    document.getElementById('copyFeedUrl')?.addEventListener('click', function () {
      const input = this.previousElementSibling;
      input.select();
      document.execCommand('copy');
      this.textContent = 'Copied';
      setTimeout(() => this.textContent = 'Copy', 1200);
    });
  </script>
  @endpush
@endsection
