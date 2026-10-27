<?php

namespace App\Livewire;

use App\Models\Article;
use Livewire\Component;

class SearchBox extends Component
{
    public string $query = '';
    public bool $showResults = false;

    public function updatedQuery()
    {
        $this->showResults = strlen($this->query) >= 2;
    }

    public function render()
    {
        $results = collect();

        if (strlen($this->query) >= 2) {
            $escaped = str_replace(['%', '_', '\\'], ['\\%', '\\_', '\\\\'], $this->query);
            $results = Article::published()
                ->where('title', 'like', "%{$escaped}%")
                ->with('category')
                ->latest('published_at')
                ->take(5)
                ->get();
        }

        return view('livewire.search-box', [
            'results' => $results,
        ]);
    }
}
