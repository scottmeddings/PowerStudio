{{-- resources/views/auth/forgot-password.blade.php --}}
<!doctype html>
<html lang="{{ str_replace('_','-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Powerpod · Forgot password</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    :root { --brand-start:#6366f1; --brand-end:#06b6d4; }
    body{min-height:100vh;background:linear-gradient(135deg,#f8f9fa,#eef2f7);}

    /* Glassy card (same look as login) */
    .auth-card{
      max-width:440px;
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

        <h1 class="h4 fw-semibold mb-3">Forgot your password?</h1>
        <p class="text-secondary small mb-4">
          No problem. Enter your email address and we’ll send you a link to reset it.
        </p>

        {{-- Session status (e.g., "We have emailed your password reset link!") --}}
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

        <form method="POST" action="{{ route('password.email') }}" class="needs-validation" novalidate>
          @csrf

          <div class="mb-3">
            <label for="email" class="form-label">Email address</label>
            <input id="email" type="email" name="email"
                   class="form-control form-control-lg"
                   value="{{ old('email') }}" required autofocus autocomplete="email">
            <div class="invalid-feedback">Please enter a valid email.</div>
          </div>

          <button class="btn btn-primary btn-lg w-100" type="submit">
            Email Password Reset Link
          </button>
        </form>

        <div class="text-center mt-4">
          <a href="{{ route('login') }}" class="small link-dark text-decoration-none">
            ← Back to sign in
          </a>
        </div>

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
