<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>

<rss version="2.0"
     xmlns:atom="http://www.w3.org/2005/Atom"
     xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd">
<channel>
  <!-- Required/standard -->
  <title>{{ e($site['title']) }}</title>
  <link>{{ rtrim($site['link'], '/') }}</link>
  <language>{{ $site['lang'] ?? 'en-us' }}</language>
  <description>{{ e($site['desc']) }}</description>
  <lastBuildDate>{{ now()->toRfc2822String() }}</lastBuildDate>
  <atom:link href="{{ rtrim($site['link'], '/') }}/feed.xml" rel="self" type="application/rss+xml" />

  <!-- Strongly recommended for podcast directories -->
  @if(!empty($site['itunes_author']))<itunes:author>{{ e($site['itunes_author']) }}</itunes:author>@endif
  @if(!empty($site['itunes_summary']))<itunes:summary><![CDATA[{!! nl2br(e($site['itunes_summary'])) !!}]]></itunes:summary>@endif
  @if(!empty($site['itunes_image']))<itunes:image href="{{ $site['itunes_image'] }}" />@endif
  @if(!empty($site['owner_name']) || !empty($site['owner_email']))
    <itunes:owner>
      @if(!empty($site['owner_name']))<itunes:name>{{ e($site['owner_name']) }}</itunes:name>@endif
      @if(!empty($site['owner_email']))<itunes:email>{{ e($site['owner_email']) }}</itunes:email>@endif
    </itunes:owner>
  @endif
  <itunes:explicit>{{ !empty($site['explicit']) && $site['explicit'] ? 'yes' : 'no' }}</itunes:explicit>
  @if(!empty($site['category']))<itunes:category text="{{ e($site['category']) }}" />@endif
  @if(!empty($site['type']))<itunes:type>{{ e($site['type']) }}</itunes:type>@endif

@foreach ($episodes as $ep)
  @php
    $appUrl   = rtrim(config('app.url'), '/');
    $itemUrl  = $appUrl . route('episodes.show', $ep, false);
    $pubDate  = optional($ep->published_at ?? $ep->created_at)?->toRfc2822String();

    // Audio URL (absolute)
    $audioUrl = $ep->audio_url;
    if (!$audioUrl && !empty($ep->audio_path)) {
        $audioUrl = \Storage::disk('public')->url($ep->audio_path);
    }
    if ($audioUrl && !preg_match('#^https?://#i', $audioUrl)) {
        $audioUrl = $appUrl . '/' . ltrim($audioUrl, '/');
    }

    // Byte length for enclosure (recommended/required by many apps)
    $audioBytes = $ep->audio_bytes ?? null; // populate from DB or storage metadata

    $itunesDuration = $ep->duration_seconds ? gmdate('H:i:s', (int) $ep->duration_seconds) : null;
    $episodeNum     = $ep->episode_number ?? null;
    $episodeType    = $ep->episode_type   ?? 'full'; // full|trailer|bonus
    $explicitItem   = isset($ep->explicit) ? ($ep->explicit ? 'yes' : 'no') : null;
  @endphp

  <item>
    <title>{{ e($ep->title) }}</title>
    <guid isPermaLink="false">{{ $ep->uuid ?? $ep->id }}</guid>
    <link>{{ $itemUrl }}</link>
    @if($pubDate)<pubDate>{{ $pubDate }}</pubDate>@endif

    <description><![CDATA[{!! nl2br(e($ep->description)) !!}]]></description>

    @if($audioUrl)
      <enclosure url="{{ $audioUrl }}" type="audio/mpeg" @if($audioBytes) length="{{ (int)$audioBytes }}" @endif />
    @endif

    @if($itunesDuration)<itunes:duration>{{ $itunesDuration }}</itunes:duration>@endif
    @if(!is_null($episodeNum))<itunes:episode>{{ (int)$episodeNum }}</itunes:episode>@endif
    @if($episodeType)<itunes:episodeType>{{ e($episodeType) }}</itunes:episodeType>@endif
    @if(!is_null($explicitItem))<itunes:explicit>{{ $explicitItem }}</itunes:explicit>@endif
    @if(!empty($ep->image_url))<itunes:image href="{{ $ep->image_url }}" />@endif
  </item>
@endforeach

</channel>
</rss>
