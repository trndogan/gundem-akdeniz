<?php

namespace App\Livewire;

use App\Models\Article;
use App\Models\City;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class CityNews extends Component
{
    public ?int $selectedCityId = null;
    public $cities = [];

    public function mount()
    {
        $this->cities = City::active()->inMenu()->orderBy('sort_order')->get();
        if ($this->cities->isNotEmpty()) {
            $this->selectedCityId = $this->cities->first()->id;
        }
    }

    public function selectCity(int $cityId)
    {
        $this->selectedCityId = $cityId;
    }

    public function render()
    {
        $articles = collect();

        if ($this->selectedCityId) {
            $articles = Cache::remember("city_news.{$this->selectedCityId}", 300, function () {
                return Article::published()
                    ->byCity($this->selectedCityId)
                    ->with(['category', 'city'])
                    ->latest('published_at')
                    ->take(4)
                    ->get();
            });
        }

        return view('livewire.city-news', [
            'articles' => $articles,
        ]);
    }
}
