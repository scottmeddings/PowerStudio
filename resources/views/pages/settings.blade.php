{{-- resources/views/pages/settings.blade.php --}}
@extends('layouts.app')

@section('title', 'Settings')
@section('page-title', 'settings')

@push('styles')
<style>
  .cover-tile{ border:1px dashed rgba(0,0,0,.12); }
  .muted-hint{ color:#6b7280; font-size:.85rem; }
  .avatar-wrap{
    width:108px;height:108px;border-radius:999px;overflow:hidden;
    display:inline-grid;place-items:center;background:#f1f5f9;border:1px solid rgba(0,0,0,.08);
  }
  .avatar-wrap img{ width:100%; height:100%; object-fit:cover; }
</style>
@endpush

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

@php($u = auth()->user())
@php($coverUrl  = $u->podcast_cover_url ?? $u->cover_url ?? ($u->cover_path ? \Storage::url($u->cover_path) : null))
@php($avatarUrl = $u->profile_photo_path ? \Storage::url($u->profile_photo_path) : ($u->profile_photo_url ?? null))
@php($creds     = method_exists($u, 'webAuthnCredentials') ? $u->webAuthnCredentials()->get() : collect())
@php($verifiedParam = request()->boolean('verified'))
@php($hostHint = parse_url(config('app.url'), PHP_URL_HOST) ?: 'this site')

{{-- FLASH / BANNERS --}}
@if ($verifiedParam || session('status') === 'email-verified')
  <div class="alert alert-success d-flex align-items-center" role="alert">
    <i class="bi bi-patch-check me-2"></i> Your email has been verified. Thanks!
  </div>
@endif

@if (session('status') === 'verification-link-sent' || session('resent') || session('ok') === 'Verification link sent.')
  <div class="alert alert-success d-flex align-items-center" role="alert">
    <i class="bi bi-check-circle me-2"></i>
    <div>Verification link sent to <strong>{{ $u->email }}</strong>.</div>
  </div>
@endif

@if (session('status') === 'already-verified')
  <div class="alert alert-info d-flex align-items-center" role="alert">
    <i class="bi bi-info-circle me-2"></i> Your email is already verified. No email was sent.
  </div>
@endif

@if (session('verification.error'))
  <div class="alert alert-danger d-flex align-items-center" role="alert">
    <i class="bi bi-exclamation-triangle me-2"></i> {{ session('verification.error') }}
  </div>
@endif

@if ($errors->any() && !$errors->has('verification'))
  <div class="alert alert-danger"><strong>Heads up:</strong> please fix the errors below.</div>
@endif

<div class="row g-3">
  {{-- LEFT: Account / Security --}}
  <div class="col-12 col-lg-7">

    <div class="section-card p-4">
      <h5 class="mb-3">Account</h5>

      {{-- Avatar --}}
      <div class="d-flex align-items-center gap-3 mb-3">
        <div class="avatar-wrap">
          <img src="{{ $avatarUrl ?: 'https://placehold.co/216x216?text=Avatar' }}" alt="Profile photo">
        </div>

        <div class="flex-grow-1">
          <form class="d-flex flex-wrap gap-2" method="POST"
                action="{{ route('settings.profile-photo') }}" enctype="multipart/form-data">
            @csrf
            <input type="file" name="photo" accept="image/png,image/jpeg,image/webp"
                   class="form-control @error('photo') is-invalid @enderror" style="max-width:280px">
            @error('photo') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            <button type="submit" class="btn btn-dark">
              <i class="bi bi-upload me-1"></i>Upload photo
            </button>
          </form>

          @if($avatarUrl)
            <form method="POST" action="{{ route('settings.profile-photo.remove') }}" class="mt-2">
              @csrf @method('DELETE')
              <button class="btn btn-outline-danger btn-sm" type="submit">
                <i class="bi bi-trash me-1"></i>Remove photo
              </button>
            </form>
          @endif

          <div class="muted-hint mt-2">JPG/PNG/WebP up to 2MB. A square image looks best.</div>
        </div>
      </div>

      {{-- ===== Verification banner lives OUTSIDE the account form ===== --}}
      @if (method_exists($u, 'hasVerifiedEmail') && ! $u->hasVerifiedEmail())
        <form method="POST" action="{{ route('verification.send') }}" class="mb-3" id="verify-resend-form">
          @csrf
          <div class="alert alert-warning d-flex align-items-center justify-content-between" role="alert">
            <div class="me-3">
              <i class="bi bi-envelope-exclamation me-2"></i>
              Your email isn’t verified.
              <small class="text-muted">
                Open the link on the same origin as <code>{{ $hostHint }}</code>.
              </small>
            </div>
            <button type="submit" class="btn btn-dark btn-sm" id="btn-resend">
              <span class="btn-text">Resend link</span>
              <span class="spinner-border spinner-border-sm ms-2 d-none" role="status" aria-hidden="true"></span>
            </button>
          </div>
        </form>
      @else
        <div class="alert alert-success d-flex align-items-center" role="alert">
          <i class="bi bi-patch-check me-2"></i> Email verified
        </div>
      @endif
      {{-- ===== End standalone verification form ===== --}}

      {{-- Account details form --}}
      <form method="POST" action="{{ route('settings.account') }}" class="needs-validation" novalidate>
        @csrf
        @method('PATCH')

        <div class="mb-3">
          <label class="form-label">Name</label>
          <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                 value="{{ old('name', $u->name) }}" required>
          @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="mb-3">
          <label class="form-label">Email</label>
          <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                 value="{{ old('email', $u->email) }}" required>
          @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <button class="btn btn-dark" type="submit">
          <i class="bi bi-save me-1"></i>Save changes
        </button>
      </form>
    </div>

    <div class="section-card p-4 mt-3">
      <h5 class="mb-3">Security</h5>
      <form method="POST" action="{{ route('password.update') }}">
        @csrf
        @method('PUT')

        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Current password</label>
            <input type="password" name="current_password"
                   class="form-control @error('current_password') is-invalid @enderror" required>
            @error('current_password') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-md-4">
            <label class="form-label">New password</label>
            <input type="password" name="password"
                   class="form-control @error('password') is-invalid @enderror" required>
            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-md-4">
            <label class="form-label">Confirm new password</label>
            <input type="password" name="password_confirmation" class="form-control" required>
          </div>
        </div>

        <button class="btn btn-dark mt-3" type="submit">
          <i class="bi bi-shield-lock me-1"></i>Update password
        </button>
      </form>
    </div>

    {{-- Passkeys --}}
    <div class="section-card p-4 mt-3">
      <div class="d-flex align-items-center justify-content-between mb-2">
        <h5 class="mb-0">Passkeys</h5>
        <span id="pk-support" class="badge bg-secondary d-none">Unsupported</span>
      </div>
      <p class="text-secondary small mb-3">
        Use Windows Hello, Touch ID, Face ID, or a security key to sign in without a password.
      </p>

      <div class="d-flex gap-2 mb-3">
        <button id="btn-create-passkey" class="btn btn-dark"
                data-options-url="{{ route('passkeys.register.options') }}"
                data-register-url="{{ route('passkeys.register') }}">
          <span class="spinner-border spinner-border-sm me-2 d-none" id="pk-spin" role="status" aria-hidden="true"></span>
          <i class="bi bi-key me-1"></i><span id="pk-text">Create Passkey</span>
        </button>
      </div>

      @if($creds->isNotEmpty())
        <ul class="list-group mb-2" id="passkey-list">
          @foreach ($creds as $cred)
            <li class="list-group-item d-flex justify-content-between align-items-center">
              <div>
                <div class="fw-semibold">{{ $cred->alias ?? 'Unnamed device' }}</div>
                <div class="small text-secondary">
                  Added {{ \Carbon\Carbon::parse($cred->created_at)->diffForHumans() }}
                  @if(!empty($cred->aaguid)) · AAGUID {{ $cred->aaguid }} @endif
                </div>
              </div>
              <form method="POST" action="{{ route('passkeys.destroy', $cred->id) }}"
                    onsubmit="return confirm('Remove this passkey? You won’t be able to sign in with it anymore.');">
                @csrf @method('DELETE')
                <button class="btn btn-outline-danger btn-sm" type="submit">Remove</button>
              </form>
            </li>
          @endforeach
        </ul>
      @else
        <div class="text-secondary small">No passkeys yet.</div>
      @endif

      <details class="mt-2">
        <summary class="small text-muted">Debug</summary>
        <pre id="passkey-debug" class="small text-muted mt-2" style="white-space:pre-wrap;max-height:38vh;overflow:auto"></pre>
      </details>
    </div>
  </div>

  {{-- RIGHT rail --}}
  <div class="col-12 col-lg-5">
    <div class="section-card p-3 cover-tile text-center">
      <h6 class="mb-2">Podcast Cover Art</h6>

      <img src="{{ $coverUrl ?: 'https://placehold.co/480x480?text=Cover' }}" alt="Podcast cover" class="img-fluid rounded mb-2">

      <form method="POST" action="{{ route('settings.cover.upload') }}" enctype="multipart/form-data" class="d-grid gap-2">
        @csrf
        <input id="coverInput" type="file" name="cover" accept="image/png,image/jpeg" class="form-control">
        @error('cover') <div class="text-danger small">{{ $message }}</div> @enderror

        <button class="btn btn-dark" type="submit"><i class="bi bi-upload me-1"></i>Upload image</button>
      </form>

      @if($coverUrl)
        <form method="POST" action="{{ route('settings.cover.delete') }}" class="mt-2">
          @csrf @method('DELETE')
          <button class="btn btn-outline-danger btn-sm" type="submit"><i class="bi bi-trash me-1"></i>Remove cover</button>
        </form>
      @endif

      <div class="muted-hint mt-2">Recommended: square JPG/PNG between 1400 and 2048 px.</div>
    </div>

    <div class="section-card p-4 mt-3">
      <h6 class="mb-2">Podcast RSS</h6>
      <div class="input-group mb-2">
        <input type="text" class="form-control" id="rssUrl" value="{{ $rss ?? url('/feed/podcast.xml') }}" readonly>
        <button type="button" class="btn btn-dark" id="copyRss">
          <i class="bi bi-clipboard-check"></i> Copy
        </button>
      </div>
      <small class="text-secondary">Submit this URL to directories (Apple, Spotify, etc.).</small>
    </div>

    <div class="section-card p-4 mt-3">
      <h6 class="text-danger mb-2">Danger zone</h6>
      <p class="text-secondary small mb-3">Deleting your account removes all episodes and analytics. This cannot be undone.</p>
      <form method="POST" action="{{ route('profile.destroy') }}"
            onsubmit="return confirm('This will permanently delete your account and data. Continue?')">
        @csrf
        @method('DELETE')
        <button class="btn btn-outline-danger"><i class="bi bi-trash me-1"></i>Delete account</button>
      </form>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  // copy RSS
  document.getElementById('copyRss')?.addEventListener('click', () => {
    const el = document.getElementById('rssUrl');
    el.select(); el.setSelectionRange(0, 99999);
    if (navigator.clipboard) navigator.clipboard.writeText(el.value);
  });

  // resend progress UX
  (function(){
    const form = document.getElementById('verify-resend-form');
    const btn  = document.getElementById('btn-resend');
    if (!form || !btn) return;
    form.addEventListener('submit', () => {
      btn.disabled = true;
      btn.querySelector('.btn-text').textContent = 'Sending…';
      btn.querySelector('.spinner-border').classList.remove('d-none');
    });
  })();

  // ===== Passkeys (unchanged core flow) =====
  const $ = (s, r=document) => r.querySelector(s);
  const debug = (m) => { const el = $('#passkey-debug'); if (!el) return;
    el.textContent += (typeof m === 'string' ? m : JSON.stringify(m, null, 2)) + '\n'; };

  const b64urlToBuf = s => Uint8Array.from(atob(s.replace(/-/g,'+').replace(/_/g,'/')), c => c.charCodeAt(0));
  const hexToBuf    = h => Uint8Array.from((h.match(/.{1,2}/g) || []).map(b => parseInt(b, 16)));
  const toBuf = v => { if (v == null) throw new Error('bad_options_shape');
    if (typeof v !== 'string') return new Uint8Array(v);
    if (/^[0-9a-f]+$/i.test(v) && v.length % 2 === 0) return hexToBuf(v);
    return b64urlToBuf(v); };
  const bufToB64url = b => { let bin=''; new Uint8Array(b).forEach(v => bin += String.fromCharCode(v));
    return btoa(bin).replace(/\+/g,'-').replace(/\//g,'_').replace(/=+$/,''); };

  function normalizeAttestationOptions(json){
    const pk = json?.publicKey ?? json;
    if (!pk?.challenge || !pk?.user?.id) throw new Error('bad_options_shape');
    const out = { ...pk };
    out.challenge = toBuf(pk.challenge);
    out.user = { ...pk.user, id: toBuf(pk.user.id) };
    out.excludeCredentials = (pk.excludeCredentials || []).map(c => ({ ...c, id: toBuf(c.id) }));
    out.attestation = out.attestation ?? 'none';
    out.authenticatorSelection = out.authenticatorSelection ?? {
      residentKey: 'required', requireResidentKey: true, userVerification: 'required', authenticatorAttachment: 'platform'
    };
    return { publicKey: out };
  }

  (async () => {
    const badge = document.getElementById('pk-support');
    if (!('PublicKeyCredential' in window)) { badge?.classList.remove('d-none'); return; }
    const hasPlatform = await PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable();
    if (!hasPlatform) badge?.classList.remove('d-none');
  })();

  document.getElementById('btn-create-passkey')?.addEventListener('click', async (ev) => {
    const btn  = ev.currentTarget;
    const spin = document.getElementById('pk-spin');
    const txt  = document.getElementById('pk-text');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

    if (location.protocol !== 'https:' && location.hostname !== 'localhost') {
      alert('Passkeys require HTTPS (except on localhost).'); return;
    }

    const optionsUrl  = btn.dataset.optionsUrl;
    const registerUrl = btn.dataset.registerUrl;

    try {
      btn.disabled = true; spin.classList.remove('d-none'); txt.textContent = 'Creating…';

      const o = await fetch(optionsUrl, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin'
      });
      if (!o.ok) throw new Error('options_http_' + o.status + ' ' + (await o.text()).slice(0,500));
      const { publicKey } = normalizeAttestationOptions(await o.json());

      const cred = await navigator.credentials.create({ publicKey });
      if (!cred) throw new Error('create_cancelled');

      const payload = {
        id: cred.id,
        rawId: bufToB64url(cred.rawId),
        type: cred.type,
        response: {
          clientDataJSON:    bufToB64url(cred.response.clientDataJSON),
          attestationObject: bufToB64url(cred.response.attestationObject)
        }
      };

      const s = await fetch(registerUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrf,
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(payload),
        credentials: 'same-origin'
      });
      if (!s.ok) throw new Error('store_http_' + s.status + ' ' + (await s.text()).slice(0,500));

      alert('Passkey created!');
      location.reload();
    } catch (e) {
      console.error(e);
      debug(['error', e?.name || 'Error', e?.message || e]);
      alert('Passkey registration failed: ' + (e?.message || e));
    } finally {
      btn.disabled = false; spin.classList.add('d-none'); txt.textContent = 'Create Passkey';
    }
  });
</script>
@endpush
