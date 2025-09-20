{{-- resources/views/auth/verify-email.blade.php --}}
<!doctype html>
<html lang="{{ str_replace('_','-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Powerpod · Verify Email</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

  <style>
    :root { --brand-start:#6366f1; --brand-end:#06b6d4; }
    body{min-height:100vh;background:linear-gradient(135deg,#f8f9fa,#eef2f7);}

    /* === Glassy, slightly opaque auth card (same as login) === */
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

    /* Fullscreen photo background (same asset key as login) */
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

    /* Brand accent underline */
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
          <i class="bi bi-envelope-paper-heart" style="font-size:2rem;color:#6366f1;"></i>
        </div>
        <h1 class="h5 text-center mb-2">Verify your email</h1>
        <div class="brand-underline mx-auto mb-3"></div>

        {{-- Copy --}}
        <p class="text-secondary text-center mb-4">
          We’ve sent a verification link to your email. Please click the link to activate your account.
          Didn’t get it? You can resend a new verification email below.
        </p>

        {{-- Success flash when re-sent --}}
        @if (session('status') === 'verification-link-sent')
          <div class="alert alert-success d-flex align-items-center" role="alert">
            <i class="bi bi-check-circle me-2"></i>
            <div>A new verification link has been sent to your email address.</div>
          </div>
        @endif

        {{-- Actions --}}
        <div class="d-grid gap-2 mt-4">
          <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="btn btn-primary btn-lg w-100">
              <i class="bi bi-arrow-repeat me-1"></i> Resend Verification Email
            </button>
          </form>

          <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-outline-secondary w-100">
              <i class="bi bi-box-arrow-right me-1"></i> Log Out
            </button>
          </form>
        </div>

        {{-- Tips --}}
        <div class="mt-3 text-center">
          <small class="text-secondary">
            Tip: Check your spam folder, and allow a minute for delivery.
          </small>
        </div>

      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
