{{-- resources/views/feed/podcast.blade.php --}}
{!! '<'.'?xml version="1.0" encoding="UTF-8"?>'."\n" !!}
<!--  generator="PodPower/1"  -->
@php
  use Illuminate\Support\Str;

  $nowRss    = now()->toRssString();

  // absolute URL helper
  $abs = function (?string $url) use ($site) {
    if (!$url) return null;
    return Str::startsWith($url, ['http://','https://'])
      ? $url
      : rtrim($site['link'], '/') . '/' . ltrim($url, '/');
  };

  // sensible defaults
  $channelTtl   = $site['ttl'] ?? 1440;
  $copyright    = $site['copyright'] ?? ('Copyright '.date('Y').' All rights reserved.');
  $generator    = 'https://podpower.com/?v=1';  // match structure

  // Self vs iTunes new-feed-url (support both controller shapes)
  $selfFeedUrl      = $site['self_feed_url'] ?? url('/feed.xml');
  $itunesNewFeedUrl = $itunes['new_feed_url'] ?? ($site['itunes_new_feed_url'] ?? $selfFeedUrl);

  // iTunes owner (support old $site[...] and new $itunes[...])
  $ownerName  = $itunes['owner_name']  ?? ($site['owner_name']  ?? null);
  $ownerEmail = $itunes['owner_email'] ?? ($site['owner_email'] ?? null);

  // Other iTunes fields (support both)
  $itunesBlock     = $itunes['block']      ?? ($site['itunes_block'] ?? 'No');
  $itunesImageHref = $itunes['image_href'] ?? ($site['itunes_image'] ?? null);

  // explicit must be "true"/"false" strings
  $explicitBool   = $itunes['explicit'] ?? ($site['itunes_explicit'] ?? false);
  $itunesExplicit = $explicitBool ? 'true' : 'false';
@endphp

<rss version="2.0"
  xmlns:content="http://purl.org/rss/1.0/modules/content/"
  xmlns:wfw="http://wellformedweb.org/CommentAPI/"
  xmlns:dc="http://purl.org/dc/elements/1.1/"
  xmlns:atom="http://www.w3.org/2005/Atom"
  xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd"
  xmlns:googleplay="http://www.google.com/schemas/play-podcasts/1.0"
  xmlns:spotify="http://www.spotify.com/ns/rss"
  xmlns:podcast="https://podcastindex.org/namespace/1.0"
  xmlns:media="http://search.yahoo.com/mrss/">

  <channel>
    <title>{{ $site['title'] }}</title>
    <atom:link href="{{ $selfFeedUrl }}" rel="self" type="application/rss+xml"/>
    <link>{{ $site['link'] }}</link>
    <description>{{ $site['desc'] }}</description>
    <pubDate>{{ $nowRss }}</pubDate>
    <generator>{{ $generator }}</generator>
    <language>{{ $site['lang'] }}</language>
    <copyright>{{ $copyright }}</copyright>
    @if(!empty($site['category']))<category>{{ $site['category'] }}</category>@endif
    <ttl>{{ $channelTtl }}</ttl>

    <itunes:type>{{ $site['itunes_type'] ?? 'episodic' }}</itunes:type>
    <itunes:summary>{{ $site['desc'] }}</itunes:summary>
    <itunes:author>{{ $site['itunes_author'] ?? '' }}</itunes:author>
    @if(!empty($site['itunes_category']))<itunes:category text="{{ $site['itunes_category'] }}"/>@endif

    @if($ownerName)
      <itunes:owner>
        <itunes:name>{{ $ownerName }}</itunes:name>
        @if(!empty($ownerEmail))<itunes:email>{{ $ownerEmail }}</itunes:email>@endif
      </itunes:owner>
    @endif

    <itunes:block>{{ $itunesBlock }}</itunes:block>
    <itunes:explicit>{{ $itunesExplicit }}</itunes:explicit>
    <itunes:new-feed-url>{{ $itunesNewFeedUrl }}</itunes:new-feed-url>

    @if(!empty($itunesImageHref))
      <itunes:image href="{{ $itunesImageHref }}"/>
      <image>
        <url>{{ $itunesImageHref }}</url>
        <title>{{ $site['title'] }}</title>
        <link>{{ $site['link'] }}</link>
        <width>144</width>
        <height>144</height>
      </image>
    @endif

    @foreach($episodes as $ep)
      @php
        $pageUrl    = $abs($ep->page_url ?? url('e/'.($ep->slug ?? $ep->id)));
        $comments   = $ep->comments_url ?? null;
        $guid       = $ep->guid ?? ($ep->uuid ?: ('episode-' . $ep->id));
        $pubDate    = optional($ep->published_at ?? $ep->created_at)->toRssString();
        $enclosure  = $abs($ep->audio_url ?? null);
        $length     = $ep->audio_length ?? null; // bytes
        $mime       = $ep->audio_mime ?? 'audio/mpeg';
        $epExplicit = !empty($ep->itunes_explicit) ? 'true' : $itunesExplicit;
        // summaries
        $plainSummary = '';
        if (!empty($ep->description)) {
          $plainSummary = trim(preg_replace('/\s+/', ' ', strip_tags($ep->description)));
        }
      @endphp
      <item>
        <title>{{ $ep->title }}</title>
        <itunes:title>{{ $ep->title }}</itunes:title>
        <link>{{ $pageUrl }}</link>
        @if($comments)<comments>{{ $comments }}</comments>@endif
        <pubDate>{{ $pubDate }}</pubDate>
        <guid isPermaLink="false">{{ $guid }}</guid>

        @if(!empty($ep->description))
          <description><![CDATA[{!! $ep->description !!}]]></description>
          <content:encoded><![CDATA[{!! $ep->description !!}]]></content:encoded>
        @endif
        @if($enclosure)
          <enclosure url="{{ $enclosure }}" @if($length) length="{{ $length }}" @endif type="{{ $mime }}"/>
        @endif
        @if(!empty($ep->description))
          <itunes:summary><![CDATA[ {{ $plainSummary }} ]]></itunes:summary>
        @endif

        

        <itunes:author>{{ $site['itunes_author'] ?? '' }}</itunes:author>
        <itunes:explicit>{{ $epExplicit }}</itunes:explicit>
        <itunes:block>{{ $ep->itunes_block ?? 'No' }}</itunes:block>

        @if(!empty($ep->itunes_duration))
          <itunes:duration>{{ $ep->itunes_duration }}</itunes:duration>
        @endif
        @if(!empty($ep->episode_number))
          <itunes:episode>{{ $ep->episode_number }}</itunes:episode>
        @endif
        <itunes:episodeType>{{ $ep->itunes_episode_type ?? 'full' }}</itunes:episodeType>

        @if(!empty($ep->image_url))
          <itunes:image href="{{ $abs($ep->image_url) }}"/>
        @endif

        @if(!empty($ep->transcript_url))
          <podcast:transcript url="{{ $ep->transcript_url }}" type="{{ $ep->transcript_type ?? 'text/vtt' }}"/>
        @endif
        @if(!empty($ep->chapters_url))
          <podcast:chapters url="{{ $ep->chapters_url }}" type="{{ $ep->chapters_type ?? 'application/json' }}"/>
        @endif
      </item>
    @endforeach
  </channel>
</rss>
