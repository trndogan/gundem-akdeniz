<?php

use App\Http\Controllers\ArticleController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\MayorController;
use App\Http\Controllers\PharmacyController;
use App\Http\Controllers\TipController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\WeatherController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

// Nöbetçi Eczaneler
Route::get('/nobetci-eczaneler', [PharmacyController::class, 'index'])->name('pharmacy.index');
Route::get('/{citySlug}/nobetci-eczaneler', [PharmacyController::class, 'show'])
    ->where('citySlug', '[a-z0-9-]+')
    ->name('pharmacy.show');

// İş İlanları
Route::get('/is-ilanlari', [JobController::class, 'index'])->name('job.index');
Route::get('/is-ilanlari/ilan-ver', [JobController::class, 'create'])->name('job.create');
Route::post('/is-ilanlari/ilan-ver', [JobController::class, 'store'])->name('job.store');
Route::get('/is-ilanlari/{slug}', [JobController::class, 'show'])
    ->where('slug', '[a-z0-9-]+')
    ->name('job.show');

// Haber Gönder
Route::get('/haber-gonder', [TipController::class, 'create'])->name('tip.create');
Route::post('/haber-gonder', [TipController::class, 'store'])->name('tip.store');

// Belediye Başkanları
Route::get('/belediye-baskanlari', [MayorController::class, 'index'])->name('mayor.index');
Route::get('/belediye-baskanlari/{slug}', [MayorController::class, 'show'])
    ->where('slug', '[a-z0-9-]+')
    ->name('mayor.show');

// Hava Durumu
Route::get('/hava-durumu', [WeatherController::class, 'index'])->name('weather.index');
Route::get('/{citySlug}/hava-durumu', [WeatherController::class, 'show'])
    ->where('citySlug', '[a-z0-9-]+')
    ->name('weather.show');

// Haber detay: /{folder}/{slug}-h{id}
Route::get('/{folder}/{articleSlug}', [ArticleController::class, 'show'])
    ->where('folder', '[a-z0-9-]+')
    ->where('articleSlug', '.*-h[0-9]+')
    ->name('article.show');

// Eski URL'lerden 301 redirect
Route::get('/haber/{idSlug}', function (string $idSlug) {
    $parts = explode('-', $idSlug, 2);
    $id = (int) $parts[0];
    $article = \App\Models\Article::with(['city', 'category'])->findOrFail($id);
    return redirect($article->url, 301);
})->where('idSlug', '[0-9]+-.*');

Route::get('/haber/{slug}', function (string $slug) {
    $article = \App\Models\Article::with(['city', 'category'])->where('slug', $slug)->firstOrFail();
    return redirect($article->url, 301);
})->where('slug', '[a-z0-9-]+');

Route::get('/kategori/{slug}', function (string $slug) {
    $category = \App\Models\Category::where('slug', $slug)->firstOrFail();
    return redirect("/{$category->slug}", 301);
});
Route::get('/sehir/{slug}', function (string $slug) {
    $city = \App\Models\City::where('slug', $slug)->firstOrFail();
    return redirect("/{$city->slug}", 301);
});
Route::get('/sayfa/{slug}', function (string $slug) {
    $page = \App\Models\Page::where('slug', $slug)->where('is_active', true)->firstOrFail();
    return redirect("/{$page->slug}", 301);
});

Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/sitemap-style.xsl', [SitemapController::class, 'xsl'])->name('sitemap.xsl');
Route::get('/sitemap-style-index.xsl', [SitemapController::class, 'xslIndex'])->name('sitemap.xsl-index');
Route::get('/post-sitemap{page}.xml', [SitemapController::class, 'posts'])->where('page', '[0-9]+')->name('sitemap.posts');
Route::get('/sitemap-style-news.xsl', [SitemapController::class, 'xslNews'])->name('sitemap.xsl-news');
Route::get('/news-sitemap.xml', [SitemapController::class, 'news'])->name('sitemap.news');
Route::get('/category-sitemap.xml', [SitemapController::class, 'categories'])->name('sitemap.categories');
Route::get('/city-sitemap.xml', [SitemapController::class, 'cities'])->name('sitemap.cities');
Route::get('/page-sitemap.xml', [SitemapController::class, 'pages'])->name('sitemap.pages');
Route::get('/pharmacy-sitemap.xml', [SitemapController::class, 'pharmacies'])->name('sitemap.pharmacies');
Route::get('/weather-sitemap.xml', [SitemapController::class, 'weather'])->name('sitemap.weather');
Route::get('/job-sitemap.xml', [SitemapController::class, 'jobs'])->name('sitemap.jobs');
Route::get('/mayor-sitemap.xml', [SitemapController::class, 'mayors'])->name('sitemap.mayors');

// Dinamik slug resolver — EN SONDA olmalı (catch-all)
// Sayfa, kategori ve şehir prefix'siz: /ekonomi, /antalya, /hakkimizda
Route::get('/{slug}', [\App\Http\Controllers\SlugController::class, 'resolve'])
    ->where('slug', '[a-z0-9][a-z0-9-]*')
    ->name('slug.resolve');
