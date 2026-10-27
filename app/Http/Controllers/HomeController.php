<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Category;
use App\Models\City;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    public function index()
    {
        $headline = Cache::remember('homepage.headline', 300, function () {
            return Article::published()->headline()
                ->with(['category', 'city'])
                ->latest('published_at')
                ->first();
        });

        $featuredArticles = Cache::remember('homepage.featured', 300, function () {
            return Article::published()->featured()
                ->with(['category', 'city'])
                ->latest('published_at')
                ->take(4)
                ->get();
        });

        $latestArticles = Cache::remember('homepage.latest.32', 300, function () {
            return Article::published()
                ->with(['category', 'city'])
                ->latest('published_at')
                ->take(32)
                ->get();
        });

        $categories = Cache::remember('nav.categories', 3600, function () {
            return Category::active()->inMenu()->orderBy('sort_order')->get();
        });

        $cities = Cache::remember('nav.cities', 3600, function () {
            return City::active()->inMenu()->orderBy('sort_order')->get();
        });

        return view('home', compact(
            'headline',
            'featuredArticles',
            'latestArticles',
            'categories',
            'cities'
        ));
    }
}
