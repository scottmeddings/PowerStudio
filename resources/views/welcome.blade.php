<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sign in</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gray-50 relative overflow-hidden">
  <a href="/register"
     class="absolute top-6 right-8 text-sm font-semibold tracking-wide uppercase text-gray-700 hover:text-black">
     Create Account
  </a>

  <div class="max-w-md mx-auto pt-20 pb-12">
    <h1 class="text-center text-2xl font-semibold text-gray-800">Sign in to Your Account</h1>

    {{-- Optional flash + errors --}}
    @if (session('status'))
      <div class="mt-4 rounded-md bg-green-50 p-3 text-sm text-green-800">
        {{ session('status') }}
      </div>
    @endif

    @if ($errors->any())
      <div class="mt-4 rounded-md bg-red-50 p-3 text-sm text-red-800">
        <ul class="list-disc list-inside">
          @foreach ($errors->all() as $e)
            <li>{{ $e }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    {{-- Social buttons --}}
    <div class="mt-6 space-y-3">
      {{-- Google --}}
      <a href="{{ route('social.redirect','google') }}"
         class="w-full inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-3 text-sm font-medium shadow-sm hover:bg-gray-50">
        <svg aria-hidden="true" viewBox="0 0 24 24" class="h-5 w-5" fill="none">
          <path fill="#EA4335" d="M12 10.2v3.8h5.3c-.2 1.2-1.4 3.6-5.3 3.6-3.2 0-5.9-2.6-5.9-5.9s2.7-5.9 5.9-5.9c1.8 0 3 .7 3.7 1.4l2.5-2.4C16.9 3 14.7 2 12 2 6.9 2 2.7 6.2 2.7 11.3S6.9 20.7 12 20.7c6.1 0 8.4-4.2 8.4-6.4 0-.4 0-.7-.1-1H12z"/>
        </svg>
        <span class="ml-3">Continue with Google</span>
      </a>

      {{-- Microsoft --}}
      <a href="{{ route('social.redirect','microsoft') }}"
         class="w-full inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-3 text-sm font-medium shadow-sm hover:bg-gray-50">
        <svg aria-hidden="true" viewBox="0 0 24 24" class="h-5 w-5">
          <rect x="2"  y="2"  width="9" height="9" fill="#F25022"/>
          <rect x="13" y="2"  width="9" height="9" fill="#7FBA00"/>
          <rect x="2"  y="13" width="9" height="9" fill="#00A4EF"/>
          <rect x="13" y="13" width="9" height="9" fill="#FFB900"/>
        </svg>
        <span class="ml-3">Continue with Microsoft</span>
      </a>

      {{-- Facebook --}}
      <a href="{{ route('social.redirect','facebook') }}"
         class="w-full inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-3 text-sm font-medium shadow-sm hover:bg-gray-50">
        <svg aria-hidden="true" viewBox="0 0 24 24" class="h-5 w-5" fill="currentColor">
          <path d="M22 12.06C22 6.5 17.52 2 12 2S2 6.5 2 12.06c0 5.02 3.66 9.19 8.44 9.94v-7.03H8.08v-2.9h2.36V9.41c0-2.33 1.39-3.62 3.52-3.62.7 0 1.8.12 2.28.2v2.52h-1.29c-1.27 0-1.66.79-1.66 1.6v1.92h2.83l-.45 2.9h-2.38V22C18.34 21.25 22 17.08 22 12.06z"/>
        </svg>
        <span class="ml-3">Continue with Facebook</span>
      </a>
    </div>
  </div>
</body>
</html>
