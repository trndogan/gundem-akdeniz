<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Category;
use App\Models\City;
use App\Models\Page;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Response;

class SitemapController extends Controller
{
    public const POSTS_PER_SITEMAP = 1000;

    /**
     * Google News sitemap yalnızca son 2 günün haberlerini kabul eder;
     * daha eskisi yok sayılır. Üst sınır 1000 kayıttır.
     */
    private const NEWS_WINDOW_HOURS = 48;

    private const NEWS_MAX_URLS = 1000;

    /**
     * Google News Publisher Center'da kayıtlı yayın adıyla birebir aynı
     * olmalıdır. APP_NAME Türkçe karakter taşımadığı için ayrıca tutulur.
     */
    private const NEWS_PUBLICATION = 'Gündem Akdeniz';

    /**
     * Sitemap Index — tüm alt sitemap'leri listeler
     */
    public function index()
    {
        $content = Cache::remember('sitemap.index', 3600, function () {
            $sitemaps = [];

            // Post sitemap sayfalama
            $totalPosts = Article::published()->count();
            $totalPages = max(1, (int) ceil($totalPosts / self::POSTS_PER_SITEMAP));

            for ($i = 1; $i <= $totalPages; $i++) {
                $lastArticle = Article::published()
                    ->latest('published_at')
                    ->skip(($i - 1) * self::POSTS_PER_SITEMAP)
                    ->take(self::POSTS_PER_SITEMAP)
                    ->get()
                    ->last();

                $sitemaps[] = [
                    'loc' => url("/post-sitemap{$i}.xml"),
                    'lastmod' => $lastArticle ? $lastArticle->updated_at->toAtomString() : now()->toAtomString(),
                ];
            }

            // Google News sitemap: yalnızca son 48 saat, haber varsa listelenir
            $sonHaber = Article::published()
                ->where('published_at', '>=', now()->subHours(self::NEWS_WINDOW_HOURS))
                ->latest('published_at')
                ->first();
            if ($sonHaber) {
                $sitemaps[] = [
                    'loc' => url('/news-sitemap.xml'),
                    'lastmod' => $sonHaber->published_at->toAtomString(),
                ];
            }

            // Category sitemap
            $lastCategory = Category::active()->latest('updated_at')->first();
            $sitemaps[] = [
                'loc' => url('/category-sitemap.xml'),
                'lastmod' => $lastCategory ? $lastCategory->updated_at->toAtomString() : now()->toAtomString(),
            ];

            // City sitemap
            $lastCity = City::active()->latest('updated_at')->first();
            $sitemaps[] = [
                'loc' => url('/city-sitemap.xml'),
                'lastmod' => $lastCity ? $lastCity->updated_at->toAtomString() : now()->toAtomString(),
            ];

            // Page sitemap
            $lastPage = Page::where('is_active', true)->latest('updated_at')->first();
            if ($lastPage) {
                $sitemaps[] = [
                    'loc' => url('/page-sitemap.xml'),
                    'lastmod' => $lastPage->updated_at->toAtomString(),
                ];
            }

            // Pharmacy sitemap
            $sitemaps[] = [
                'loc' => url('/pharmacy-sitemap.xml'),
                'lastmod' => now()->toAtomString(),
            ];

            // Weather sitemap
            $sitemaps[] = [
                'loc' => url('/weather-sitemap.xml'),
                'lastmod' => now()->toAtomString(),
            ];

            // Jobs sitemap
            $sitemaps[] = [
                'loc' => url('/job-sitemap.xml'),
                'lastmod' => now()->toAtomString(),
            ];

            // Mayor sitemap
            $sitemaps[] = [
                'loc' => url('/mayor-sitemap.xml'),
                'lastmod' => now()->toAtomString(),
            ];

            return view('sitemap.index', compact('sitemaps'))->render();
        });

        return Response::make($content, 200, ['Content-Type' => 'application/xml']);
    }

    /**
     * Google News Sitemap — son 48 saatin haberleri
     *
     * Genel post sitemap'inden ayrıdır çünkü Google News farklı bir şema
     * bekler ve yalnızca taze haberi tarar. Önbellek kısa tutulur (5 dakika);
     * ayrıca bot bir haber yayınladığında PublisherService bu anahtarı düşürür,
     * böylece haber sitemap'e anında girer.
     */
    public function news()
    {
        $content = Cache::remember('sitemap.news', 300, function () {
            $articles = Article::published()
                ->where('published_at', '>=', now()->subHours(self::NEWS_WINDOW_HOURS))
                ->latest('published_at')
                ->take(self::NEWS_MAX_URLS)
                ->get();

            $urls = $articles->map(fn ($article) => [
                'loc' => $article->url,
                'title' => $article->title,
                'published_at' => $article->published_at->toAtomString(),
            ]);

            // Haber yoksa da geçerli boş bir urlset döner; 404 vermek
            // Search Console'da kalıcı hata olarak görünür.
            return view('sitemap.news', [
                'urls' => $urls,
                'publication' => self::NEWS_PUBLICATION,
                'language' => 'tr',
            ])->render();
        });

        return Response::make($content, 200, ['Content-Type' => 'application/xml']);
    }

    /**
     * Post Sitemap — sayfalanmış haber URL'leri
     */
    public function posts(int $page = 1)
    {
        $content = Cache::remember("sitemap.posts.{$page}", 3600, function () use ($page) {
            $articles = Article::published()
                ->latest('published_at')
                ->skip(($page - 1) * self::POSTS_PER_SITEMAP)
                ->take(self::POSTS_PER_SITEMAP)
                ->get();

            if ($articles->isEmpty()) {
                abort(404);
            }

            $urls = $articles->map(function ($article) {
                return [
                    'loc' => $article->url,
                    'lastmod' => $article->updated_at->toAtomString(),
                    'changefreq' => 'daily',
                    'priority' => '0.8',
                    'image' => $article->image_display_url,
                    'title' => $article->title,
                ];
            });

            return view('sitemap.urls', ['urls' => $urls, 'type' => 'Haberler'])->render();
        });

        return Response::make($content, 200, ['Content-Type' => 'application/xml']);
    }

    /**
     * Category Sitemap
     */
    public function categories()
    {
        $content = Cache::remember('sitemap.categories', 3600, function () {
            $urls = collect();

            // Anasayfa
            $urls->push([
                'loc' => url('/'),
                'lastmod' => now()->toAtomString(),
                'changefreq' => 'hourly',
                'priority' => '1.0',
            ]);

            Category::active()->get()->each(function ($category) use ($urls) {
                $urls->push([
                    'loc' => url('/' . $category->slug),
                    'lastmod' => $category->updated_at->toAtomString(),
                    'changefreq' => 'daily',
                    'priority' => '0.7',
                ]);
            });

            return view('sitemap.urls', ['urls' => $urls, 'type' => 'Kategoriler'])->render();
        });

        return Response::make($content, 200, ['Content-Type' => 'application/xml']);
    }

    /**
     * City Sitemap
     */
    public function cities()
    {
        $content = Cache::remember('sitemap.cities', 3600, function () {
            $urls = City::active()->get()->map(function ($city) {
                return [
                    'loc' => url('/' . $city->slug),
                    'lastmod' => $city->updated_at->toAtomString(),
                    'changefreq' => 'daily',
                    'priority' => '0.7',
                ];
            });

            return view('sitemap.urls', ['urls' => $urls, 'type' => 'Şehirler'])->render();
        });

        return Response::make($content, 200, ['Content-Type' => 'application/xml']);
    }

    /**
     * Page Sitemap
     */
    public function pages()
    {
        $content = Cache::remember('sitemap.pages', 3600, function () {
            $urls = Page::where('is_active', true)->get()->map(function ($page) {
                return [
                    'loc' => url('/' . $page->slug),
                    'lastmod' => $page->updated_at->toAtomString(),
                    'changefreq' => 'monthly',
                    'priority' => '0.5',
                ];
            });

            return view('sitemap.urls', ['urls' => $urls, 'type' => 'Sayfalar'])->render();
        });

        return Response::make($content, 200, ['Content-Type' => 'application/xml']);
    }

    /**
     * Pharmacy Sitemap
     */
    public function pharmacies()
    {
        $content = Cache::remember('sitemap.pharmacies', 3600, function () {
            $urls = collect();

            // Ana nöbetçi eczane sayfası
            $urls->push([
                'loc' => route('pharmacy.index'),
                'lastmod' => now()->toAtomString(),
                'changefreq' => 'daily',
                'priority' => '0.7',
            ]);

            // Şehir bazlı sayfalar
            City::active()->get()->each(function ($city) use ($urls) {
                $urls->push([
                    'loc' => route('pharmacy.show', $city->slug),
                    'lastmod' => now()->toAtomString(),
                    'changefreq' => 'daily',
                    'priority' => '0.8',
                ]);
            });

            return view('sitemap.urls', ['urls' => $urls, 'type' => 'Nöbetçi Eczaneler'])->render();
        });

        return Response::make($content, 200, ['Content-Type' => 'application/xml']);
    }

    /**
     * Weather Sitemap
     */
    public function weather()
    {
        $content = Cache::remember('sitemap.weather', 3600, function () {
            $urls = collect();

            $urls->push([
                'loc' => route('weather.index'),
                'lastmod' => now()->toAtomString(),
                'changefreq' => 'hourly',
                'priority' => '0.7',
            ]);

            City::active()->get()->each(function ($city) use ($urls) {
                $urls->push([
                    'loc' => route('weather.show', $city->slug),
                    'lastmod' => now()->toAtomString(),
                    'changefreq' => 'hourly',
                    'priority' => '0.8',
                ]);
            });

            return view('sitemap.urls', ['urls' => $urls, 'type' => 'Hava Durumu'])->render();
        });

        return Response::make($content, 200, ['Content-Type' => 'application/xml']);
    }

    /**
     * Job Sitemap
     */
    public function jobs()
    {
        $content = Cache::remember('sitemap.jobs', 3600, function () {
            $urls = collect();

            $urls->push([
                'loc' => route('job.index'),
                'lastmod' => now()->toAtomString(),
                'changefreq' => 'daily',
                'priority' => '0.8',
            ]);

            \App\Models\JobListing::active()->get()->each(function ($job) use ($urls) {
                $urls->push([
                    'loc' => $job->url,
                    'lastmod' => $job->updated_at->toAtomString(),
                    'changefreq' => 'weekly',
                    'priority' => '0.7',
                ]);
            });

            return view('sitemap.urls', ['urls' => $urls, 'type' => 'İş İlanları'])->render();
        });

        return Response::make($content, 200, ['Content-Type' => 'application/xml']);
    }

    /**
     * Mayor Sitemap
     */
    public function mayors()
    {
        $content = Cache::remember('sitemap.mayors', 3600, function () {
            $urls = collect();

            $urls->push([
                'loc' => route('mayor.index'),
                'lastmod' => now()->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.8',
            ]);

            \App\Models\Mayor::active()->get()->each(function ($mayor) use ($urls) {
                $urls->push([
                    'loc' => $mayor->url,
                    'lastmod' => $mayor->updated_at->toAtomString(),
                    'changefreq' => 'weekly',
                    'priority' => '0.7',
                ]);
            });

            return view('sitemap.urls', ['urls' => $urls, 'type' => 'Belediye Başkanları'])->render();
        });

        return Response::make($content, 200, ['Content-Type' => 'application/xml']);
    }

    /**
     * XSL Stylesheet — tarayıcıda güzel görünüm
     */
    public function xsl()
    {
        $content = view('sitemap.style')->render();
        return Response::make($content, 200, ['Content-Type' => 'application/xml']);
    }

    /**
     * Sitemap Index XSL
     */
    public function xslIndex()
    {
        $content = view('sitemap.style-index')->render();
        return Response::make($content, 200, ['Content-Type' => 'application/xml']);
    }

    /**
     * Google News sitemap'inin XSL şablonu.
     *
     * Diğer sitemap'lerden ayrı bir şablon kullanır: news sitemap'te öncelik,
     * sıklık ve görsel alanları yoktur, bunun yerine başlık ve yayın tarihi
     * gösterilir. Tarayıcılar dışında kimse okumaz, arama motorları yok sayar.
     */
    public function xslNews()
    {
        return Response::make(
            view('sitemap.style-news')->render(),
            200,
            ['Content-Type' => 'text/xsl']
        );
    }
}
