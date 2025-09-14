{{-- resources/views/site/partials/subscribe-badges.blade.php --}}
@if(!empty($settings['show_subscribe_badges']))
  <div class="d-flex flex-wrap gap-2">
    @php
      $links = [
        ['Apple','pi-apple','https://podcasts.apple.com/'],
        ['Spotify','pi-spotify','https://open.spotify.com/'],
        ['YouTube Music','pi-ytm','https://music.youtube.com/'],
        ['Amazon','pi-amazon','https://music.amazon.com/'],
      ];
    @endphp
    @foreach($links as [$name,$icon,$url])
      <a class="btn btn-sm btn-outline-light"
         style="--bs-btn-color:#fff;--bs-btn-border-color:rgba(255,255,255,.35)"
         href="{{ $url }}" target="_blank" rel="noopener">
        <i class="{{ $icon }} me-1"></i>{{ $name }}
      </a>
    @endforeach
  </div>
@endif
