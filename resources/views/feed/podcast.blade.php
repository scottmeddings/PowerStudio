{{-- resources/views/feed/podcast.blade.php --}}
{!! '<'.'?xml version="1.0" encoding="UTF-8"?>'."\n" !!}

@php
use Illuminate\Support\Str;

$nowRss    = now()->toRssString();
$lastBuild = optional(optional($episodes->first())->updated_at)->toRssString() ?? $nowRss;

$abs = function (?string $url) use ($site) {
    if (!$url) return null;
    return Str::startsWith($url, ['http://','https://'])
        ? $url
        : rtrim($site['link'], '/') . '/' . ltrim($url, '/');
};
@endphp

<rss version="2.0"
  xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd"
  xmlns:atom="http://www.w3.org/2005/Atom"
  xmlns:content="http://purl.org/rss/1.0/modules/content/"
  xmlns:podcast="https://podcastindex.org/namespace/1.0">
  <channel>
    <title>{{ $site['title'] }}</title>
    <atom:link href="{{ $site['self_feed_url'] }}" rel="self" type="application/rss+xml"/>
    <link>{{ $site['link'] }}</link>
    <description><![CDATA[{{ $site['desc'] }}]]></description>
    <pubDate>{{ $nowRss }}</pubDate>
    <lastBuildDate>{{ $lastBuild }}</lastBuildDate>
    <language>{{ $site['lang'] }}</language>

    <itunes:type>{{ $site['itunes_type'] }}</itunes:type>
    <itunes:author>{{ $site['itunes_author'] }}</itunes:author>
    <itunes:category text="{{ $site['itunes_category'] }}"/>
    <itunes:owner>
      <itunes:name>{{ $site['owner_name'] }}</itunes:name>
      @if(!empty($site['owner_email']))<itunes:email>{{ $site['owner_email'] }}</itunes:email>@endif
    </itunes:owner>
    <itunes:explicit>false</itunes:explicit>

    @if(!empty($site['itunes_image']))
      <itunes:image href="{{ $site['itunes_image'] }}"/>
      <image>
        <url>{{ $site['itunes_image'] }}</url>
        <title>{{ $site['title'] }}</title>
        <link>{{ $site['link'] }}</link>
      </image>
    @endif

    @foreach($episodes as $ep)
      @php
        $pageUrl   = url('e/'.($ep->slug ?? $ep->id));
        $enclosure = $abs($ep->audio_url ?? null);
        $guid      = $ep->guid ?? ('episode-'.$ep->id);
        $pubDate   = optional($ep->published_at ?? $ep->created_at)->toRssString();
      @endphp
      <item>
        <title>{{ $ep->title }}</title>
        <link>{{ $pageUrl }}</link>
        <guid isPermaLink="false">{{ $guid }}</guid>
        <pubDate>{{ $pubDate }}</pubDate>

        @if(!empty($ep->description))
          <description><![CDATA[{!! $ep->description !!}]]></description>
          <content:encoded><![CDATA[{!! $ep->description !!}]]></content:encoded>
          <itunes:summary><![CDATA[{!! strip_tags($ep->description) !!}]]></itunes:summary>
        @endif

        @if($enclosure)
          <enclosure url="{{ $enclosure }}" type="audio/mpeg"
            @if(!empty($ep->audio_length)) length="{{ $ep->audio_length }}" @endif />
        @endif

        @if(!empty($ep->itunes_duration))
          <itunes:duration>{{ $ep->itunes_duration }}</itunes:duration>
        @endif
        @if(!empty($ep->episode_number))
          <itunes:episode>{{ $ep->episode_number }}</itunes:episode>
        @endif

        <itunes:episodeType>full</itunes:episodeType>
        <itunes:explicit>false</itunes:explicit>
      </item>
    @endforeach
  </channel>
</rss>
