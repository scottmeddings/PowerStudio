{{-- resources/views/site/partials/episode-card.blade.php --}}
@php
  $img  = $ep->cover_image ? (Str::startsWith($ep->cover_image, ['http://','https://']) ? $ep->cover_image : asset('storage/'.$ep->cover_image)) : asset('images/episode-fallback.jpg');
  $date = $ep->published_at ? \Illuminate\Support\Carbon::parse($ep->published_at)->format('M j, Y') : null;
  $desc = trim(Str::limit(strip_tags($ep->description ?? ''), 180));
@endphp
<div class="card h-100 shadow-sm">
  <img class="card-img-top" src="{{ $img }}" alt="{{ $ep->title ?? 'Episode' }}">
  <div class="card-body d-flex flex-column">
    <h5 class="card-title mb-1">
      <a class="stretched-link text-decoration-none" href="{{ route('site.episode', $ep->slug) }}">{{ $ep->title ?? 'Untitled episode' }}</a>
    </h5>
    @if($date)<div class="text-muted small mb-2">{{ $date }}</div>@endif
    @if($desc)<p class="mb-0">{{ $desc }}</p>@endif
  </div>
</div>
