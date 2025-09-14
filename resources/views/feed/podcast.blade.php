{{-- resources/views/feed/podcast.blade.php --}}
@php
  use Illuminate\Support\Facades\Storage;
  $nowRss = now()->toRssString();
  $lastBuild = optional(optional($episodes->first())->updated_at)->toRssString() ?? $nowRss;
@endphp

<rss version="2.0"
     xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd">
  <channel>
    <title>{{ $site['title'] }}</title>
    <link>{{ $site['link'] }}</link>
    <language>{{ $site['lang'] }}</language>
    <description><![CDATA[{{ $site['desc'] }}]]></description>
    <lastBuildDate>{{ $lastBuild }}</lastBuildDate>
    <itunes:author>{{ $site['title'] }}</itunes:author>
    <itunes:explicit>false</itunes:explicit>
    {{-- Optional channel image (replace with your logo URL) --}}
    {{-- <itunes:image href="{{ $site['link'] }}/images/podcast-cover.jpg" /> --}}

@foreach ($episodes as $ep)
@php
  // Absolute episode page URL
  $pageUrl = url('/episodes/'.($ep->slug ?? $ep->id));
  // Enclosure URL should be absolute
  $enclosureUrl = $ep->audio_url ? url($ep->audio_url) : null;

  // Best-effort content length (recommended for Apple)
  $length = null;
  if ($ep->audio_path && Storage::disk('public')->exists($ep->audio_path)) {
      try { $length = Storage::disk('public')->size($ep->audio_path); } catch (\Throwable $e) {}
  }

  $guid = $ep->guid ?? ('episode-'.$ep->id);
  $pub  = optional($ep->published_at ?? $ep->created_at)->toRssString();
  $durationSec = optional(optional($ep->transcript)->duration_ms, fn($ms) => (int) floor($ms/1000));
@endphp
    <item>
      <title>{{ $ep->title }}</title>
      <link>{{ $pageUrl }}</link>
      <guid isPermaLink="false">{{ $guid }}</guid>
      <pubDate>{{ $pub }}</pubDate>
      <description><![CDATA[{!! nl2br(e($ep->description ?? '')) !!}]]></description>
      @if ($enclosureUrl)
        <enclosure url="{{ $enclosureUrl }}" type="audio/mpeg"@if($length) length="{{ $length }}"@endif />
      @endif
      @if (!is_null($durationSec))
        <itunes:duration>{{ $durationSec }}</itunes:duration>
      @endif
      <itunes:explicit>false</itunes:explicit>
    </item>
@endforeach

  </channel>
</rss>
