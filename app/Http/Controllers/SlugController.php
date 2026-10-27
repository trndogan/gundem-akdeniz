<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\City;
use App\Models\Page;
use App\Models\Article;
use Illuminate\Support\Facades\Cache;

class SlugController extends Controller
{
    public function resolve(string $slug)
    {
        // 1. Önce statik sayfalar
        $page = Page::where('slug', $slug)->where('is_active', true)->first();
        if ($page) {
            return view('page.show', compact('page'));
        }

        // 2. Sonra kategoriler
        $category = Category::where('slug', $slug)->first();
        if ($category) {
            $articles = Article::published()
                ->byCategory($category->id)
                ->with(['category', 'city'])
                ->latest('published_at')
                ->paginate(12);

            return view('article.category', compact('category', 'articles'));
        }

        // 3. Sonra şehirler
        $city = City::where('slug', $slug)->first();
        if ($city) {
            $articles = Article::published()
                ->byCity($city->id)
                ->with(['category', 'city'])
                ->latest('published_at')
                ->paginate(12);

            return view('article.city', compact('city', 'articles'));
        }

        abort(404);
    }
}
