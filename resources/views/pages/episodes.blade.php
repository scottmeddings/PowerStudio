@extends('layouts.app')

@section('title', 'Episodes')
@section('page-title', 'Episodes')

@push('styles')
<style>
  /* Compact table */
  .section-card.compact       { padding:.5rem .5rem 0; border-radius:.6rem; }
  .section-card.compact .table{ margin-bottom:0; }
  .table-episodes> :not(caption)>*>*{ padding:.55rem .65rem; }

  /* Title cell: truncate long titles, keep comments snug */
  .title-cell{ display:flex; align-items:center; gap:.5rem; min-width:0; }
  .title-text{ min-width:0; max-width: 58ch; }
  .title-text .text-truncate{ display:block; }
  @media (min-width: 1400px){
    .title-text{ max-width: 72ch; }
  }

  /* Badges, meta */
  .badge-compact{ font-size:.72rem; font-weight:600; padding:.28rem .45rem; }
  .badge-comments{ background:#f1f5f9; color:#475569; border:1px solid #e2e8f0; }
  .meta-muted{ color:#6b7280; font-size:.86rem; }

  /* Numbers: right-aligned, tabular digits */
  .num{ text-align:right; font-variant-numeric: tabular-nums; }

  /* Action buttons */
  .actions{ display:flex; justify-content:flex-end; gap:.35rem; flex-wrap:nowrap; white-space:nowrap; }
  .actions form{ display:inline-block; margin:0; }
  .btn-xs{
    --bs-btn-padding-y:.30rem; --bs-btn-padding-x:.62rem; --bs-btn-font-size:.80rem; line-height:1.15;
  }

  th.actions, td.actions{ width: 330px; }

  @media (max-width: 576px){
    .btn-label { display:none; }
    th.published, td.published{ display:none; }
  }
</style>
@endpush

@section('content')
  @if(session('success'))
    <div class="alert alert-success py-2 px-3">{{ session('success') }}</div>
  @endif

  <div class="d-flex justify-content-between align-items-center mb-2">
    <h6 class="mb-0">
      Your Episodes
      <span class="text-secondary ms-1">({{ number_format($episodes->total()) }})</span>
    </h6>

    @can('create', \App\Models\Episode::class)
      <a class="btn btn-dark btn-xs" data-bs-toggle="modal" data-bs-target="#episodeModal">
        <i class="bi bi-plus-lg me-1"></i><span class="btn-label">New Episode</span>
      </a>
    @endcan
  </div>

  <div class="section-card compact">
    <div class="table-responsive">
      <table class="table table-episodes align-middle">
        <thead class="table-light">
          <tr class="text-secondary">
            <th style="width:48%">Title</th>
            <th style="width:12%">Status</th>
            <th class="plays" style="width:10%; text-align:right;">Plays</th>
            <th class="published" style="width:16%">Published</th>
            <th class="actions text-end">Actions</th>
          </tr>
        </thead>

        <tbody>
        @php
          // tiny helper for compact numbers
          $shortNum = function($n){
            if ($n >= 1000000) return rtrim(rtrim(number_format($n/1000000, 1), '0'), '.').'M';
            if ($n >= 1000)    return rtrim(rtrim(number_format($n/1000, 1), '0'), '.').'k';
            return number_format($n);
          };
        @endphp

        @forelse($episodes as $ep)
          <tr>
            {{-- Title + comments --}}
            <td class="title-cell">
              <div class="title-text">
                <a href="{{ route('episodes.show', $ep) }}" class="text-decoration-none text-truncate">
                  {{ $ep->title }}
                </a>
              </div>
              @php
                $commentCount = $ep->approved_comments_count
                               ?? $ep->comments_count
                               ?? null;
              @endphp
              @if(!is_null($commentCount) && $commentCount > 0)
                <span class="badge rounded-pill badge-comments">{{ number_format($commentCount) }} comments</span>
              @endif
            </td>

            {{-- Status --}}
            <td>
              @php
                $status = strtolower($ep->status ?? 'draft');
                $map = ['published'=>'success','draft'=>'secondary','archived'=>'dark'];
                $bg  = $map[$status] ?? 'secondary';
              @endphp
              <span class="badge text-bg-{{ $bg }} badge-compact">{{ ucfirst($status) }}</span>
            </td>

            {{-- Plays (sum of rows in "downloads") --}}
          @php
                $plays = isset($ep->downloads_count)
                    ? (int) $ep->downloads_count
                    : (int) \Illuminate\Support\Facades\DB::table('downloads')
                          ->where('episode_id', $ep->id)->count();
            @endphp
            <td class="num" title="{{ number_format($plays) }} plays">
              {{ $shortNum($plays) }}
            </td>


            {{-- Published --}}
            <td class="published meta-muted">
              @if($ep->published_at)
                @php
                  $publishedAt = $ep->published_at instanceof \Carbon\Carbon
                    ? $ep->published_at
                    : \Illuminate\Support\Carbon::parse($ep->published_at);
                @endphp
                <span title="{{ $publishedAt->format('Y-m-d H:i') }}">{{ $publishedAt->diffForHumans() }}</span>
              @else
                —
              @endif
            </td>

            {{-- Actions --}}
            <td class="actions text-end">
              @can('update', $ep)
                @if(strtolower($ep->status ?? 'draft') !== 'published')
                  {{-- Publish --}}
                  <form method="POST" action="{{ route('episodes.publish', $ep) }}">
                    @csrf @method('PATCH')
                    <button class="btn btn-success btn-xs" type="submit">
                      <i class="bi bi-megaphone me-1"></i><span class="btn-label">Publish</span>
                    </button>
                  </form>
                @else
                  {{-- Unpublish --}}
                  <form method="POST" action="{{ route('episodes.unpublish', $ep) }}">
                    @csrf @method('PATCH')
                    <button class="btn btn-warning btn-xs" type="submit">
                      <i class="bi bi-arrow-counterclockwise me-1"></i><span class="btn-label">Unpublish</span>
                    </button>
                  </form>
                @endif

                <a href="{{ route('episodes.edit', $ep) }}" class="btn btn-outline-secondary btn-xs">
                  <i class="bi bi-pencil me-1"></i><span class="btn-label">Edit</span>
                </a>
              @endcan

              @can('delete', $ep)
                <form method="POST" action="{{ route('episodes.destroy', $ep) }}"
                      onsubmit="return confirm('Delete \"{{ $ep->title }}\"?');">
                  @csrf @method('DELETE')
                  <button class="btn btn-outline-danger btn-xs" type="submit">
                    <i class="bi bi-trash me-1"></i><span class="btn-label">Delete</span>
                  </button>
                </form>
              @endcan
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="5" class="text-center text-secondary py-4">No episodes yet.</td>
          </tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div class="mt-2">
    {{ $episodes->withQueryString()->links('pagination::bootstrap-5') }}
  </div>
@endsection
