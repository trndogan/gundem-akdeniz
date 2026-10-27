<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Mayor;
use App\Models\Party;
use Illuminate\Http\Request;

class MayorController extends Controller
{
    public function index(Request $request)
    {
        $query = Mayor::active()
            ->with(['city', 'party'])
            ->withCount('articles')
            ->orderBy('sort_order');

        if ($request->filled('party')) {
            $party = Party::where('slug', $request->party)->first();
            if ($party) {
                $query->where('party_id', $party->id);
            }
        }

        if ($request->filled('city')) {
            $city = City::where('slug', $request->city)->first();
            if ($city) {
                $query->byCity($city->id);
            }
        }

        $mayors = $query->get();
        $parties = Party::orderBy('sort_order')->get();
        $cities = City::active()->orderBy('sort_order')->get();

        // Trend: En çok haberi yapılan başkanlar
        $trending = Mayor::active()
            ->with(['city', 'party'])
            ->withCount('articles')
            ->orderByDesc('articles_count')
            ->take(5)
            ->get();

        return view('mayors.index', compact('mayors', 'parties', 'cities', 'trending'));
    }

    public function show(string $slug)
    {
        $mayor = Mayor::active()
            ->where('slug', $slug)
            ->with(['city', 'party'])
            ->firstOrFail();

        $articles = $mayor->articles()
            ->published()
            ->with(['category', 'city'])
            ->take(12)
            ->get();

        $pressReleases = $mayor->pressReleases()
            ->published()
            ->with(['category', 'city'])
            ->take(6)
            ->get();

        return view('mayors.show', compact('mayor', 'articles', 'pressReleases'));
    }
}
