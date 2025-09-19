{{-- resources/views/auth/register.blade.php --}}
<!doctype html>
<html lang="{{ str_replace('_','-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Powerpod · Create account</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    :root { --brand-start:#6366f1; --brand-end:#06b6d4; }
    body{min-height:100vh;background:linear-gradient(135deg,#f8f9fa,#eef2f7);}

    /* Glassy card (same look as login) */
    .auth-card{
      max-width:480px;
      position:relative;
      z-index:2;
      background: rgba(255,255,255,.82)!important;
      backdrop-filter: saturate(120%) blur(8px);
      -webkit-backdrop-filter: saturate(120%) blur(8px);
      border: 1px solid rgba(255,255,255,.55);
      box-shadow: 0 20px 50px rgba(0,0,0,.25);
      border-radius: 1rem;
    }
    .auth-card .card-body{ background: transparent; }
    .btn-icon svg{margin-right:.5rem}

    /* Fullscreen photo background */
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

  <!-- Background -->
  <div class="bg-podcast" aria-hidden="true"></div>

  <div class="container py-5">
    <div class="mx-auto auth-card card border-0">
      <div class="card-body p-4 p-md-5">
        <h1 class="h4 fw-semibold mb-3">Create your account</h1>

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

        {{-- Social sign-up (actually sign-in/up via OAuth) --}}
        <div class="d-grid gap-2 mb-4">
          <a class="btn btn-outline-secondary btn-lg d-flex align-items-center justify-content-center btn-icon"
             href="{{ route('social.redirect','google') }}">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
              <path fill="#EA4335" d="M12 10.2v3.8h5.3c-.2 1.2-1.4 3.6-5.3 3.6-3.2 0-5.9-2.6-5.9-5.9s2.7-5.9 5.9-5.9c1.8 0 3 .7 3.7 1.4l2.5-2.4C16.9 3 14.7 2 12 2 6.9 2 2.7 6.2 2.7 11.3S6.9 20.7 12 20.7c6.1 0 8.4-4.2 8.4-6.4 0-.4 0-.7-.1-1H12z"/>
            </svg>
            Continue with Google
          </a>

          <a class="btn btn-outline-secondary btn-lg d-flex align-items-center justify-content-center btn-icon"
             href="{{ route('social.redirect','microsoft') }}">
            <svg width="18" height="18" viewBox="0 0 24 24">
              <rect x="2" y="2" width="9" height="9" fill="#F25022"/>
              <rect x="13" y="2" width="9" height="9" fill="#7FBA00"/>
              <rect x="2" y="13" width="9" height="9" fill="#00A4EF"/>
              <rect x="13" y="13" width="9" height="9" fill="#FFB900"/>
            </svg>
            Continue with Microsoft
          </a>

          <a class="btn btn-outline-secondary btn-lg d-flex align-items-center justify-content-center btn-icon"
             href="{{ route('social.redirect','facebook') }}">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
              <path d="M22 12.06C22 6.5 17.52 2 12 2S2 6.5 2 12.06c0 5.02 3.66 9.19 8.44 9.94v-7.03H8.08v-2.9h2.36V9.41c0-2.33 1.39-3.62 3.52-3.62.7 0 1.8.12 2.28.2v2.52h-1.29c-1.27 0-1.66.79-1.66 1.6v1.92h2.83l-.45 2.9h-2.38V22C18.34 21.25 22 17.08 22 12.06z"/>
            </svg>
            Continue with Facebook
          </a>
        </div>

        <div class="text-center text-muted small mb-3">or</div>

        {{-- Registration form --}}
        <form method="POST" action="{{ route('register') }}" class="needs-validation" novalidate>
          @csrf

          <div class="mb-3">
            <label for="name" class="form-label">Name</label>
            <input id="name" type="text" name="name"
                   class="form-control form-control-lg"
                   value="{{ old('name') }}" required autofocus autocomplete="name">
            <div class="invalid-feedback">Please enter your name.</div>
          </div>

          <div class="mb-3">
            <label for="email" class="form-label">Email address</label>
            <input id="email" type="email" name="email"
                   class="form-control form-control-lg"
                   value="{{ old('email') }}" required autocomplete="username">
            <div class="invalid-feedback">Please enter a valid email.</div>
          </div>

          <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input id="password" type="password" name="password"
                   class="form-control form-control-lg"
                   required autocomplete="new-password">
            <div class="invalid-feedback">Password is required.</div>
          </div>

          <div class="mb-4">
            <label for="password_confirmation" class="form-label">Confirm password</label>
            <input id="password_confirmation" type="password" name="password_confirmation"
                   class="form-control form-control-lg"
                   required autocomplete="new-password">
            <div class="invalid-feedback">Please confirm your password.</div>
          </div>

          <button class="btn btn-primary btn-lg w-100" type="submit">Create account</button>
        </form>

        <p class="text-center small text-secondary mt-4 mb-0">
          Already registered?
          <a href="{{ route('login') }}" class="link-dark fw-semibold">Sign in</a>
        </p>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Client-side Bootstrap validation
    (() => {
      'use strict';
      const forms = document.querySelectorAll('.needs-validation');
      Array.from(forms).forEach(form => {
        form.addEventListener('submit', e => {
          if (!form.checkValidity()) { e.preventDefault(); e.stopPropagation(); }
          form.classList.add('was-validated');
        }, false);
      });
    })();
  </script>
</body>
</html>
