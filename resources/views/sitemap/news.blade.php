@php echo '<' . '?xml version="1.0" encoding="UTF-8"?' . '>'; @endphp
@php echo '<' . '?xml-stylesheet type="text/xsl" href="' . route('sitemap.xsl-news') . '"?' . '>'; @endphp
{{-- Google News sitemap.
     Genel sitemap'ten ayrıdır: yalnızca son 48 saatin haberlerini taşır ve
     her kayıtta yayın adı, dil, yayın tarihi ve başlık bulunur. Google News
     bu dosyayı sık tarar, haberin dakikalar içinde indekslenmesini sağlar.
     changefreq/priority/lastmod alanları Google News tarafından yok sayıldığı
     için eklenmez. --}}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">
@foreach($urls as $url)
    <url>
        <loc>{{ $url['loc'] }}</loc>
        <news:news>
            <news:publication>
                <news:name>{{ htmlspecialchars($publication, ENT_XML1) }}</news:name>
                <news:language>{{ $language }}</news:language>
            </news:publication>
            <news:publication_date>{{ $url['published_at'] }}</news:publication_date>
            <news:title>{{ htmlspecialchars($url['title'], ENT_XML1) }}</news:title>
        </news:news>
    </url>
@endforeach
</urlset>
