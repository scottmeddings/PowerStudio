@extends('layouts.app')

@section('title','Admin · Users')
@section('page-title','User Management')

@section('content')
<div class="container-fluid px-0 px-lg-2">

  {{-- Flash / Errors --}}
  @if(session('ok'))
    <div class="alert alert-success d-flex align-items-center" role="alert">
      <i class="bi bi-check-circle me-2"></i> {{ session('ok') }}
    </div>
  @endif

  @if($errors->any())
    <div class="alert alert-danger" role="alert">
      <div class="d-flex">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <div>
          <strong>There were some issues:</strong>
          <ul class="mb-0 mt-1">
            @foreach($errors->all() as $e)
              <li>{{ $e }}</li>
            @endforeach
          </ul>
        </div>
      </div>
    </div>
  @endif

  {{-- Toolbar --}}
  <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 mb-3">
    <div>
      <h2 class="h5 mb-0">Team & Access</h2>
      <small class="text-secondary">Create users, assign roles, and manage access</small>
    </div>

    <div class="d-flex align-items-center gap-2">
      <form method="GET" action="{{ route('admin.users.index') }}" class="d-none d-md-flex" role="search">
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-search"></i></span>
          <input name="q" value="{{ request('q') }}" class="form-control" placeholder="Search name or email">
          @if(request()->filled('q'))
            <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
          @endif
        </div>
      </form>
      <button class="btn btn-blush" data-bs-toggle="modal" data-bs-target="#addUserModal">
        <i class="bi bi-plus-lg me-1"></i>Add User
      </button>
    </div>
  </div>

  {{-- Add User (Card + Modal) --}}
  <div class="section-card p-3 p-md-4 mb-4">
    <div class="d-flex align-items-center justify-content-between">
      <div class="fw-semibold">Quick Add</div>
      <button class="btn btn-sm btn-outline-secondary d-md-none" data-bs-toggle="modal" data-bs-target="#addUserModal">
        <i class="bi bi-plus-lg me-1"></i>Add
      </button>
    </div>
    <form method="POST" action="{{ route('admin.users.store') }}" class="row g-3 mt-1">
      @csrf
      <div class="col-12 col-md-4">
        <label class="form-label">Name</label>
        <input name="name" class="form-control @error('name') is-invalid @enderror" required value="{{ old('name') }}">
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>
      <div class="col-12 col-md-4">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" required value="{{ old('email') }}">
        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>
      <div class="col-8 col-md-3">
        <label class="form-label">Role</label>
        <select name="role" class="form-select @error('role') is-invalid @enderror" required>
          @foreach($roles as $r)
            <option value="{{ $r }}" @selected(old('role')===$r)>{{ ucfirst($r) }}</option>
          @endforeach
        </select>
        @error('role') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>
      <div class="col-4 col-md-1 d-flex align-items-end">
        <button class="btn btn-primary w-100">Add</button>
      </div>
      <div class="col-12">
        <small class="text-secondary">An invite (password reset link) will be emailed to the new user.</small>
      </div>
    </form>
  </div>

  {{-- Users Table --}}
  <div class="section-card">
    <div class="d-flex align-items-center justify-content-between p-3 border-bottom">
      <div class="fw-semibold">All Users</div>
      <div class="text-secondary small">
        {{ $users->total() }} total
      </div>
    </div>

    <div class="table-responsive">
      <table class="table align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th style="width:56px;">ID</th>
            <th>Name</th>
            <th>Email</th>
            <th class="text-nowrap" style="width:240px;">Role</th>
            <th class="text-end" style="width:160px;">Actions</th>
          </tr>
        </thead>
        <tbody>
        @forelse($users as $u)
          <tr>
            <td class="text-secondary">{{ $u->id }}</td>
            <td class="fw-medium">{{ $u->name }}</td>
            <td>
              <a href="mailto:{{ $u->email }}" class="link-secondary text-decoration-none">{{ $u->email }}</a>
            </td>
            <td>
              <div class="d-flex align-items-center gap-2">
                {{-- Role pill --}}
                @php
                  $pillMap = [
                    'admin'    => 'bg-danger-subtle text-danger-emphasis border border-danger-subtle',
                    'creator'  => 'bg-primary-subtle text-primary-emphasis border border-primary-subtle',
                    'readonly' => 'bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle',
                    'user'     => 'bg-light text-secondary border',
                  ];
                  $pillClass = $pillMap[$u->role] ?? 'bg-light text-secondary border';
                @endphp
                <span class="badge rounded-pill px-3 py-2 {{ $pillClass }}">{{ ucfirst($u->role) }}</span>

                {{-- Inline role changer --}}
                <form method="POST" action="{{ route('admin.users.role',$u) }}" class="ms-auto">
                  @csrf @method('PATCH')
                  <select name="role" class="form-select form-select-sm" onchange="this.form.submit()">
                    @foreach($roles as $r)
                      <option value="{{ $r }}" @selected($u->role===$r)>{{ ucfirst($r) }}</option>
                    @endforeach
                  </select>
                </form>
              </div>
            </td>
            <td class="text-end">
              <div class="btn-group">
                <a href="mailto:{{ $u->email }}" class="btn btn-sm btn-outline-secondary" title="Email">
                  <i class="bi bi-envelope"></i>
                </a>
                <form method="POST" action="{{ route('admin.users.destroy',$u) }}"
                      onsubmit="return confirm('Delete {{ $u->name }}?');">
                  @csrf @method('DELETE')
                  <button class="btn btn-sm btn-outline-danger" @disabled(auth()->id()===$u->id) title="Delete">
                    <i class="bi bi-trash"></i>
                  </button>
                </form>
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="5" class="text-center text-secondary py-5">
              <i class="bi bi-people mb-2 d-block" style="font-size:1.4rem;"></i>
              No users yet.
            </td>
          </tr>
        @endforelse
        </tbody>
      </table>
    </div>

    <div class="p-3 border-top d-flex justify-content-end">
      {{ $users->withQueryString()->links() }}
    </div>
  </div>

</div>

{{-- Add User Modal (mobile-friendly duplicate of quick add) --}}
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form method="POST" action="{{ route('admin.users.store') }}" class="modal-content">
      @csrf
      <div class="modal-header">
        <h5 class="modal-title" id="addUserLabel"><i class="bi bi-person-plus me-2"></i>Add User</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Name</label>
          <input name="name" class="form-control" required value="{{ old('name') }}">
        </div>
        <div class="mb-3">
          <label class="form-label">Email</label>
          <input type="email" name="email" class="form-control" required value="{{ old('email') }}">
        </div>
        <div>
          <label class="form-label">Role</label>
          <select name="role" class="form-select" required>
            @foreach($roles as $r)
              <option value="{{ $r }}" @selected(old('role')===$r)>{{ ucfirst($r) }}</option>
            @endforeach
          </select>
        </div>
        <small class="text-secondary d-block mt-2">An invite (password reset link) will be emailed to the new user.</small>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-blush">Add User</button>
      </div>
    </form>
  </div>
</div>
@endsection
