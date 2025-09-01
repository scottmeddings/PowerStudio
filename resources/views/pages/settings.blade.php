@extends('layouts.app')

@section('title', 'Settings')
@section('page-title', 'settings')

@section('content')
@php($u = auth()->user())

{{-- Flash messages --}}
@if (session('status'))
  <div class="alert alert-success">{{ session('status') }}</div>
@endif
@if (session('success'))
  <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if ($errors->any())
  <div class="alert alert-danger">
    <strong>Heads up:</strong> please fix the errors below.
  </div>
@endif

<div class="row g-3">
  {{-- Account --}}
  <div class="col-12 col-lg-7">
    <div class="section-card p-4">
      <h5 class="mb-3">Account</h5>
      <form method="POST" action="{{ route('profile.update') }}" class="needs-validation" novalidate>
        @csrf
        @method('PATCH')

        <div class="mb-3">
          <label class="form-label">Name</label>
          <input type="text" name="name"
                 class="form-control @error('name') is-invalid @enderror"
                 value="{{ old('name', $u->name) }}" required>
          @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="mb-3">
          <label class="form-label">Email</label>
          <input type="email" name="email"
                 class="form-control @error('email') is-invalid @enderror"
                 value="{{ old('email', $u->email) }}" required>
          @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        {{-- Optional: show verification status if you use email verification --}}
        @if (method_exists($u, 'hasVerifiedEmail') && !$u->hasVerifiedEmail())
          <div class="alert alert-warning d-flex align-items-center" role="alert">
            <i class="bi bi-envelope-exclamation me-2"></i>
            Your email isn’t verified.
            <form method="POST" action="{{ route('verification.send') }}" class="ms-2">
              @csrf
              <button class="btn btn-sm btn-outline-dark">Resend link</button>
            </form>
          </div>
        @endif

        <div class="d-flex gap-2">
          <button class="btn btn-dark" type="submit">
            <i class="bi bi-save me-1"></i>Save changes
          </button>
        </div>
      </form>
    </div>

    {{-- Password --}}
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

        <div class="d-flex gap-2 mt-3">
          <button class="btn btn-dark" type="submit">
            <i class="bi bi-shield-lock me-1"></i>Update password
          </button>
        </div>
      </form>
    </div>
  </div>

  {{-- Right rail --}}
  <div class="col-12 col-lg-5">
 {{-- RSS / API (read-only helpers) --}}
<div class="section-card p-4">
  <h6 class="mb-2">Podcast RSS</h6>

  <div class="input-group mb-2">
    <input type="text" class="form-control" id="rssUrl"
           value="{{ $rss ?? url('/feed/podcast.xml') }}" readonly>
    <button type="button" class="btn btn-outline-secondary" id="copyRss">
      <i class="bi bi-clipboard-check"></i> Copy
    </button>
  </div>

  <small class="text-secondary">
    Submit this URL to directories (Apple, Spotify, etc.).
  </small>
</div>


    {{-- Danger Zone --}}
    <div class="section-card p-4 mt-3">
      <h6 class="text-danger mb-2">Danger zone</h6>
      <p class="text-secondary small mb-3">Deleting your account removes all episodes and analytics. This cannot be undone.</p>
      <form method="POST" action="{{ route('profile.destroy') }}"
            onsubmit="return confirm('This will permanently delete your account and data. Continue?')">
        @csrf
        @method('DELETE')
        <button class="btn btn-outline-danger">
          <i class="bi bi-trash me-1"></i>Delete account
        </button>
      </form>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  // Copy RSS helper
  document.getElementById('copyRss')?.addEventListener('click', () => {
    const el = document.getElementById('rssUrl');
    el.select(); el.setSelectionRange(0, 99999);
    if (navigator.clipboard) navigator.clipboard.writeText(el.value);
  });
</script>
@endpush
