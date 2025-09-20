{{-- resources/views/auth/confirm-password.blade.php --}}
<!doctype html>
<html lang="{{ str_replace('_','-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Powerpod · Confirm Password</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

  <style>
    :root { --brand-start:#6366f1; --brand-end:#06b6d4; }
    body{min-height:100vh;background:linear-gradient(135deg,#f8f9fa,#eef2f7);}

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
          <i class="bi bi-lock-fill" style="font-size:2rem;color:#6366f1;"></i>
        </div>
        <h1 class="h5 text-center mb-2">Confirm your password</h1>
        <div class="brand-underline mx-auto mb-4"></div>

        <p class="text-secondary text-center mb-4">
          This is a secure area of the application. Please confirm your password before continuing.
        </p>

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

        <form method="POST" action="{{ route('password.confirm') }}" class="needs-validation" novalidate>
          @csrf

          <div class="mb-4">
            <label for="password" class="form-label">Password</label>
            <input id="password"
                   type="password"
                   class="form-control form-control-lg @error('password') is-invalid @enderror"
                   name="password" required autocomplete="current-password">
            @error('password') <div class="invalid-feedback">{{ $message }}</div> @else
              <div class="invalid-feedback">Please enter your password.</div>
            @enderror
          </div>

          <button class="btn btn-primary btn-lg w-100" type="submit">
            <i class="bi bi-shield-check me-1"></i> Confirm
          </button>
        </form>

        <div class="text-center mt-3">
          <a href="{{ url()->previous() }}" class="link-secondary small">
            <i class="bi bi-arrow-left me-1"></i> Go back
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
