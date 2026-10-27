@php echo '<' . '?xml version="1.0" encoding="UTF-8"?' . '>'; @endphp
@php echo '<' . '?xml-stylesheet type="text/xsl" href="' . route('sitemap.xsl-index') . '"?' . '>'; @endphp
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach($sitemaps as $sitemap)
    <sitemap>
        <loc>{{ $sitemap['loc'] }}</loc>
        <lastmod>{{ $sitemap['lastmod'] }}</lastmod>
    </sitemap>
@endforeach
</sitemapindex>
