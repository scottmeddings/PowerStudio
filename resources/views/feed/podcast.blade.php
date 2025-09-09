<?php echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL; ?>
<rss version="2.0"
     xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd"
     xmlns:atom="http://www.w3.org/2005/Atom"
     xmlns:podcast="https://podcastindex.org/namespace/1.0">
  <channel>
    <title>{{ $config['title'] }}</title>
    <link>{{ $config['site_url'] }}</link>
    <atom:link href="{{ $self }}" rel="self" type="application/rss+xml"/>
    <language>{{ $config['language'] }}</language>
    <copyright>{{ date('Y') }} {{ $config['owner_name'] }}</copyright>
    <description><![CDATA[{{ $config['description'] }}]]></description>

    <itunes:author><![CDATA[{{ $config['author'] }}]]></itunes:author>
    <itunes:summary><![CDATA[{{ $config['description'] }}]]></itunes:summary>
    <itunes:owner>
      <itunes:name>{{ $config['owner_name'] }}</itunes:name>
      <itunes:email>{{ $config['owner_email'] }}</itunes:email>
    </itunes:owner>
    <itunes:explicit>{{ $config['explicit'] }}</itunes:explicit>
    <itunes:type>episodic</itunes:type>
    <itunes:category text="{{ $config['category'] }}"/>
    <itunes:image href="{{ $config['cover_url'] }}"/>

    <lastBuildDate>{{ $lastBuildDate }}</lastBuildDate>

    @foreach($items as $item)
      <item>
        <title><![CDATA[{{ $item['title'] }}]]></title>
        <description><![CDATA[{{ $item['description'] }}]]></description>
        <link>{{ $item['guid'] }}</link>
        <guid isPermaLink="false">{{ $item['guid'] }}</guid>
        @if($item['pubDate'])<pubDate>{{ $item['pubDate'] }}</pubDate>@endif

        <enclosure url="{{ $item['enclosure']['url'] }}"
                   length="{{ $item['enclosure']['length'] }}"
                   type="{{ $item['enclosure']['type'] }}" />

        @if(!empty($item['duration'])) <itunes:duration>{{ $item['duration'] }}</itunes:duration> @endif
        <itunes:explicit>{{ $config['explicit'] }}</itunes:explicit>

        {{-- Episode-level image if present --}}
        @if(!empty($item['cover']))
          <itunes:image href="{{ $item['cover'] }}"/>
        @endif

        {{-- Podcasting 2.0: transcript --}}
        @if($item['transcript'])
          <podcast:transcript url="{{ $item['transcript']['url'] }}"
                              type="{{ $item['transcript']['type'] }}" />
        @endif

        {{-- Podcasting 2.0: chapters (JSON) --}}
        @if($item['chapters_url'])
          <podcast:chapters url="{{ $item['chapters_url'] }}" type="application/json+chapters"/>
        @endif
      </item>
    @endforeach
  </channel>
</rss>
