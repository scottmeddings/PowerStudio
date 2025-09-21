\{{-- resources/views/auth/login.blade.php --}}
<!doctype html>
<html lang="{{ str_replace('_','-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Powerpod · Sign in</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    :root { --brand-start:#6366f1; --brand-end:#06b6d4; }
    body{min-height:100vh;background:linear-gradient(135deg,#f8f9fa,#eef2f7);}
    .auth-card{
      max-width:440px; position:relative; z-index:2;
      background: rgba(255,255,255,.82) !important;
      backdrop-filter: saturate(120%) blur(8px);
      -webkit-backdrop-filter: saturate(120%) blur(8px);
      border: 1px solid rgba(255,255,255,.55);
      box-shadow: 0 20px 50px rgba(0,0,0,.25);
      border-radius: 1rem;
    }
    .auth-card .card-body{ background: transparent; }
    .btn-icon svg{margin-right:.5rem}
    .bg-podcast{
      position:fixed; inset:0; z-index:0; pointer-events:none;
      background-image:url("{{ asset('images/powerpod-podcast-bg.png') }}");
      background-size:cover; background-position:center; background-attachment:fixed;
      filter:saturate(1.05) contrast(1.02);
    }
    .bg-podcast::after{
      content:""; position:absolute; inset:0;
      background:
        radial-gradient(1200px 600px at 20% 0%, rgba(0,0,0,.35), transparent 60%),
        radial-gradient(1000px 500px at 80% 0%, rgba(0,0,0,.25), transparent 55%),
        linear-gradient(to bottom, rgba(6,11,25,.55), rgba(6,11,25,.35));
    }
    @media (prefers-reduced-motion:reduce){ .bg-podcast{ background-attachment:scroll; } }
  </style>
</head>
<body class="d-flex align-items-center justify-content-center">

  <noscript>
    <div class="alert alert-warning position-fixed top-0 start-50 translate-middle-x mt-3" role="alert">
      JavaScript is required for passkey and social sign-in.
    </div>
  </noscript>

  <div class="bg-podcast" aria-hidden="true"></div>

  <div class="container py-5">
    <div class="mx-auto auth-card card border-0">
      <div class="card-body p-4 p-md-5">

        {{-- Session status --}}
        @if (session('status'))
          <div class="alert alert-success small">{{ session('status') }}</div>
        @endif

        {{-- Errors --}}
        @if ($errors->any())
          <div class="alert alert-danger small">
            <ul class="mb-0 ps-3">
              @foreach ($errors->all() as $e)
                <li>{{ $e }}</li>
              @endforeach
            </ul>
          </div>
        @endif

        {{-- Passkey sign-in (WebAuthn) --}}
        <div class="d-grid gap-2 mb-3">
          <button type="button" id="btn-passkey"
                  class="btn btn-outline-dark btn-lg d-flex align-items-center justify-content-center"
                  aria-live="polite">
            <span class="me-2">🔑</span> <span id="passkey-label">Sign in with Passkey</span>
          </button>
          <div id="passkey-unsupported" class="text-muted small text-center d-none">
            Passkeys aren’t supported on this device or browser.
          </div>
        </div>

        {{-- Social sign-in --}}
        <div class="d-grid gap-2 mb-4">
          <a class="btn btn-outline-secondary btn-lg d-flex align-items-center justify-content-center btn-icon"
             href="{{ route('social.redirect','google') }}">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path fill="#EA4335" d="M12 10.2v3.8h5.3c-.2 1.2-1.4 3.6-5.3 3.6-3.2 0-5.9-2.6-5.9-5.9s2.7-5.9 5.9-5.9c1.8 0 3 .7 3.7 1.4l2.5-2.4C16.9 3 14.7 2 12 2 6.9 2 2.7 6.2 2.7 11.3S6.9 20.7 12 20.7c6.1 0 8.4-4.2 8.4-6.4 0-.4 0-.7-.1-1H12z"/>
            </svg>
            Continue with Google
          </a>

          <a class="btn btn-outline-secondary btn-lg d-flex align-items-center justify-content-center btn-icon"
             href="{{ route('social.redirect','microsoft') }}">
            <svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true">
              <rect x="2" y="2" width="9" height="9" fill="#F25022"/>
              <rect x="13" y="2" width="9" height="9" fill="#7FBA00"/>
              <rect x="2" y="13" width="9" height="9" fill="#00A4EF"/>
              <rect x="13" y="13" width="9" height="9" fill="#FFB900"/>
            </svg>
            Continue with Microsoft
          </a>

          <a class="btn btn-outline-secondary btn-lg d-flex align-items-center justify-content-center btn-icon"
             href="{{ route('social.redirect','facebook') }}">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
              <path d="M22 12.06C22 6.5 17.52 2 12 2S2 6.5 2 12.06c0 5.02 3.66 9.19 8.44 9.94v-7.03H8.08v-2.9h2.36V9.41c0-2.33 1.39-3.62 3.52-3.62.7 0 1.8.12 2.28.2v2.52h-1.29c-1.27 0-1.66.79-1.66 1.6v1.92h2.83l-.45 2.9h-2.38V22C18.34 21.25 22 17.08 22 12.06z"/>
            </svg>
            Continue with Facebook
          </a>
        </div>

        {{-- Email/password --}}
        <form method="POST" action="{{ route('login') }}" class="needs-validation" novalidate>
          @csrf
          <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control form-control-lg" id="email" name="email"
                   value="{{ old('email') }}" required autocomplete="username" autofocus>
            <div class="invalid-feedback">Please enter a valid email.</div>
          </div>

          <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input type="password" class="form-control form-control-lg" id="password" name="password"
                   required autocomplete="current-password">
            <div class="invalid-feedback">Password is required.</div>
          </div>

          <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" value="1" id="remember_me" name="remember" {{ old('remember') ? 'checked' : '' }}>
              <label class="form-check-label" for="remember_me">Remember me</label>
            </div>
            @if (Route::has('password.request'))
              <a class="link-primary small" href="{{ route('password.request') }}">Forgot password?</a>
            @endif
          </div>

          <button class="btn btn-primary btn-lg w-100" type="submit">Sign in</button>
        </form>

        @if (Route::has('register'))
          <p class="text-center small text-secondary mt-4 mb-0">
            Don’t have an account?
            <a href="{{ route('register') }}" class="link-dark fw-semibold">Create one</a>
          </p>
        @endif
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    // Intended redirect (fallback to home)
    const INTENDED_URL = @json(session('url.intended', url('/')));

    // Bootstrap validation
    (() => {
      const forms = document.querySelectorAll('.needs-validation');
      Array.from(forms).forEach(form => {
        form.addEventListener('submit', e => {
          if (!form.checkValidity()) { e.preventDefault(); e.stopPropagation(); }
          form.classList.add('was-validated');
        }, false);
      });
    })();

    // Helpers
    const b64urlToBuf = s =>
      Uint8Array.from(atob(s.replace(/-/g,'+').replace(/_/g,'/')), c => c.charCodeAt(0));

    const hexToBuf = hex =>
      Uint8Array.from((hex.match(/.{1,2}/g) || []).map(b => parseInt(b, 16)));

    const toBuf = (v) => {
      if (v == null) throw new Error('bad_options_shape: missing binary');
      if (typeof v !== 'string') return new Uint8Array(v);
      if (/^[0-9a-f]+$/i.test(v) && v.length % 2 === 0) return hexToBuf(v); // hex
      return b64urlToBuf(v); // base64url
    };

    // Accept {publicKey:{…}} or flat {…}
    const normalizeAssertionOptions = (json) => {
      const pk = json?.publicKey ?? json;
      if (!pk?.challenge) throw new Error('bad_options_shape: no challenge');
      const out = { ...pk };
      out.challenge = toBuf(pk.challenge);
      out.allowCredentials = (pk.allowCredentials || []).map(c => ({ ...c, id: toBuf(c.id) }));
      return { publicKey: out };
    };

    // Passkey login
    (function initPasskeys(){
      const btn  = document.getElementById('btn-passkey');
      const note = document.getElementById('passkey-unsupported');
      const label= document.getElementById('passkey-label');
      const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

      if (!('PublicKeyCredential' in window) || !navigator.credentials) {
        btn?.classList.add('disabled'); if (btn) btn.disabled = true;
        note?.classList.remove('d-none'); return;
      }

      const setBusy = (b) => {
        if (!btn) return;
        btn.disabled = !!b;
        label.textContent = b ? 'Waiting for passkey…' : 'Sign in with Passkey';
      };

      btn?.addEventListener('click', async () => {
        setBusy(true);
        try {
          // 1) Get options
          const resp = await fetch('{{ route('passkeys.options') }}', {
            method:'POST',
            headers:{ 'X-CSRF-TOKEN': csrf, 'Accept':'application/json', 'X-Requested-With':'XMLHttpRequest' }
          });
          if (!resp.ok) throw new Error('options_http_' + resp.status + ' ' + (await resp.text()).slice(0,500));

          // 2) Normalize + convert
          const { publicKey } = normalizeAssertionOptions(await resp.json());

          // 3) Request assertion (Windows Hello, etc.)
          const cred = await navigator.credentials.get({ publicKey });
          if (!cred) throw new Error('NoCredential');

          // 4) Send to server (NO userHandle to avoid UUID validation issues)
          const bufToB64url = (b) => {
            let bin=''; new Uint8Array(b).forEach(v => bin += String.fromCharCode(v));
            return btoa(bin).replace(/\+/g,'-').replace(/\//g,'_').replace(/=+$/,'');
          };

          const verify = await fetch('{{ route('passkeys.verify') }}', {
            method:'POST',
            headers:{
              'Content-Type':'application/json',
              'X-CSRF-TOKEN': csrf,
              'Accept':'application/json',
              'X-Requested-With':'XMLHttpRequest'
            },
            body: JSON.stringify({
              id: cred.id,
              rawId: bufToB64url(cred.rawId),
              type: cred.type,
              response: {
                authenticatorData: bufToB64url(cred.response.authenticatorData),
                clientDataJSON:    bufToB64url(cred.response.clientDataJSON),
                signature:         bufToB64url(cred.response.signature)
                // no userHandle — let server resolve by credential id
              }
            })
          });

          if (!verify.ok) throw new Error('verify_http_' + verify.status + ' ' + (await verify.text()).slice(0,500));
          let data = {};
          try { data = await verify.json(); } catch {}
          window.location.href = data.redirect || INTENDED_URL;

        } catch (e) {
          console.error(e);
          const msg = e?.name || e?.message || 'UnknownError';
          alert('Passkey sign-in failed: ' + msg + '. Ensure HTTPS/localhost and a registered passkey.');
        } finally {
          setBusy(false);
        }
      });
    })();

    // Optional: log platform authenticator availability
    (async () => {
      if (!('PublicKeyCredential' in window)) return;
      const hasPlatform = await PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable();
      console.log('Platform authenticator available:', hasPlatform);
    })();
  </script>
</body>
</html>
