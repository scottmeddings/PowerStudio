<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>

<rss version="2.0"
     xmlns:atom="http://www.w3.org/2005/Atom"
     xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd">
<channel>
  <title>{{ e($site['title']) }}</title>
  <link>{{ $site['link'] }}</link>
  <language>{{ $site['lang'] }}</language>
  <description>{{ e($site['desc']) }}</description>
  <atom:link href="{{ $site['link'] }}/feed/podcast.xml" rel="self" type="application/rss+xml" />

@foreach ($episodes as $ep)
  @php
    $appUrl  = rtrim(config('app.url'), '/');
    $itemUrl = $appUrl . route('episodes.show', $ep, false);
    $pubDate = optional($ep->published_at ?? $ep->created_at)?->toRfc2822String();

    $audioUrl = $ep->audio_url;
    if (!$audioUrl && !empty($ep->audio_path)) {
        $audioUrl = \Storage::disk('public')->url($ep->audio_path);
    }
    if ($audioUrl && !preg_match('#^https?://#i', $audioUrl)) {
        $audioUrl = $appUrl . '/' . ltrim($audioUrl, '/');
    }

    $itunesDuration = $ep->duration_seconds ? gmdate('H:i:s', (int) $ep->duration_seconds) : null;
  @endphp

  <item>
    <title>{{ e($ep->title) }}</title>
    <guid isPermaLink="false">{{ $ep->id }}</guid>
    <link>{{ $itemUrl }}</link>
    @if($pubDate)<pubDate>{{ $pubDate }}</pubDate>@endif
    <description><![CDATA[{!! nl2br(e($ep->description)) !!}]]></description>
    @if($audioUrl)<enclosure url="{{ $audioUrl }}" type="audio/mpeg" />@endif
    @if($itunesDuration)<itunes:duration>{{ $itunesDuration }}</itunes:duration>@endif
  </item>
@endforeach

</channel>
</rss>
