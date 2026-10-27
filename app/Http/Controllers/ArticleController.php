<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Category;
use App\Models\City;
use Illuminate\Support\Facades\Cache;

class ArticleController extends Controller
{
    public function show(string $folder, string $articleSlug)
    {
        // URL sonundaki -h{id} kısmından ID'yi parse et
        if (!preg_match('/-h(\d+)$/', $articleSlug, $matches)) {
            abort(404);
        }
        $id = (int) $matches[1];

        $article = Article::published()
            ->where('id', $id)
            ->with(['category', 'city', 'author'])
            ->firstOrFail();

        // Canonical URL kontrolü — yanlış folder veya slug ise 301 redirect
        $expectedFolder = $article->city ? $article->city->slug : ($article->category ? $article->category->slug : 'haber');
        $expectedSlug = $article->slug . '-h' . $article->id;
        if ($folder !== $expectedFolder || $articleSlug !== $expectedSlug) {
            return redirect($article->url, 301);
        }

        $sessionKey = 'viewed_article_' . $article->id;
        if (!session()->has($sessionKey)) {
            $article->incrementViewCount();
            session()->put($sessionKey, true);
        }

        $relatedArticles = Cache::remember("article.related.{$article->id}", 600, function () use ($article) {
            return Article::published()
                ->where('id', '!=', $article->id)
                ->where('category_id', $article->category_id)
                ->latest('published_at')
                ->take(4)
                ->get();
        });

        $mostRead = Cache::remember('articles.most_read.10', 3600, function () {
            return Article::published()
                ->with('category')
                ->orderByDesc('view_count')
                ->take(10)
                ->get();
        });

        return view('article.show', compact('article', 'relatedArticles', 'mostRead'));
    }

    public function category(string $slug)
    {
        $category = Category::where('slug', $slug)->firstOrFail();

        $articles = Article::published()
            ->byCategory($category->id)
            ->with(['category', 'city'])
            ->latest('published_at')
            ->paginate(12);

        return view('article.category', compact('category', 'articles'));
    }

    public function city(string $slug)
    {
        $city = City::where('slug', $slug)->firstOrFail();

        $articles = Article::published()
            ->byCity($city->id)
            ->with(['category', 'city'])
            ->latest('published_at')
            ->paginate(12);

        return view('article.city', compact('city', 'articles'));
    }
}
