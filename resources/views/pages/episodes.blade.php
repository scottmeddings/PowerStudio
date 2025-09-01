@extends('layouts.app')

@section('title', 'episodes')
@section('page-title', 'episodes')

@section('content')
  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif

  <div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Your Episodes</h5>
     <a class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#episodeModal">
        <i class="bi bi-plus-lg me-1"></i>New Episode
    </a>

  </div>

  <div class="section-card p-0">
    <div class="table-responsive">
      <table class="table align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Title</th>
            <th>Status</th>
            <th>Published</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($episodes as $ep)
            <tr>
              <td class="fw-medium">{{ $ep->title }}</td>
              <td><span class="badge text-bg-{{ $ep->status === 'published' ? 'success' : 'secondary' }}">{{ ucfirst($ep->status) }}</span></td>
              <td>{{ $ep->published_at?->format('Y-m-d H:i') ?? '—' }}</td>
              <td class="text-end">
                <a href="{{ route('episodes.edit', $ep) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                <form method="POST" action="{{ route('episodes.destroy', $ep) }}" class="d-inline"
                      onsubmit="return confirm('Delete \"{{ $ep->title }}\"?');">
                  @csrf @method('DELETE')
                  <button class="btn btn-sm btn-outline-danger">Delete</button>
                </form>
              </td>
            </tr>
          @empty
            <tr><td colspan="4" class="text-center text-secondary py-4">No episodes yet.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div class="mt-3">
    {{ $episodes->withQueryString()->links() }}
  </div>
@endsection
