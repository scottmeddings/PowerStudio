{{-- resources/views/auth/reset-password.blade.php --}}
<!doctype html>
<html lang="{{ str_replace('_','-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Powerpod · Reset Password</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

  <style>
    :root { --brand-start:#6366f1; --brand-end:#06b6d4; }
    body{min-height:100vh;background:linear-gradient(135deg,#f8f9fa,#eef2f7);}

    /* Glassy card (same as login) */
    .auth-card{
      max-width:440px;
      position:relative;
      z-index:2;
      background: rgba(255,255,255,.82) !important;
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

    .brand-underline{
      width:64px;height:4px;border-radius:999px;
      background:linear-gradient(135deg,var(--brand-start),var(--brand-end));
    }
  </style>
</head>
<body class="d-flex align-items-center justify-content-center">

  <!-- Photo background -->
  <div class="bg-podcast" aria-hidden="true"></div>

  <div class="container py-5">
    <div class="mx-auto auth-card card border-0">
      <div class="card-body p-4 p-md-5">

        {{-- Header --}}
        <div class="text-center mb-3">
          <i class="bi bi-shield-lock" style="font-size:2rem;color:#6366f1;"></i>
        </div>
        <h1 class="h5 text-center mb-2">Reset your password</h1>
        <div class="brand-underline mx-auto mb-4"></div>

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

        <form method="POST" action="{{ route('password.store') }}" class="needs-validation" novalidate>
          @csrf

          {{-- Token --}}
          <input type="hidden" name="token" value="{{ $request->route('token') }}">

          {{-- Email --}}
          <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input id="email"
                   type="email"
                   class="form-control form-control-lg @error('email') is-invalid @enderror"
                   name="email"
                   value="{{ old('email', $request->email) }}"
                   required autocomplete="username" autofocus>
            @error('email') <div class="invalid-feedback">{{ $message }}</div> @else
              <div class="invalid-feedback">Please enter a valid email.</div>
            @enderror
          </div>

          {{-- Password --}}
          <div class="mb-3">
            <label for="password" class="form-label">New Password</label>
            <input id="password"
                   type="password"
                   class="form-control form-control-lg @error('password') is-invalid @enderror"
                   name="password"
                   required autocomplete="new-password" minlength="8">
            @error('password') <div class="invalid-feedback">{{ $message }}</div> @else
              <div class="invalid-feedback">Please enter a password (8+ characters).</div>
            @enderror
          </div>

          {{-- Confirm --}}
          <div class="mb-4">
            <label for="password_confirmation" class="form-label">Confirm Password</label>
            <input id="password_confirmation"
                   type="password"
                   class="form-control form-control-lg @error('password_confirmation') is-invalid @enderror"
                   name="password_confirmation"
                   required autocomplete="new-password">
            @error('password_confirmation') <div class="invalid-feedback">{{ $message }}</div> @else
              <div class="invalid-feedback">Please confirm your password.</div>
            @enderror
          </div>

          <button class="btn btn-primary btn-lg w-100" type="submit">
            <i class="bi bi-arrow-clockwise me-1"></i> Reset Password
          </button>
        </form>

        <div class="text-center mt-3">
          <a href="{{ route('login') }}" class="link-secondary small">
            <i class="bi bi-box-arrow-in-right me-1"></i> Back to sign in
          </a>
        </div>

      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  {{-- Client-side Bootstrap validation --}}
  <script>
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
