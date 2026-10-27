<?php

namespace App\Filament\Widgets;

use App\Models\Article;
use App\Models\Category;
use App\Models\City;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Toplam Haber', Article::count())
                ->description('Yayında: ' . Article::published()->count())
                ->color('danger')
                ->icon('heroicon-o-newspaper'),
            Stat::make('Kategoriler', Category::active()->count())
                ->color('info')
                ->icon('heroicon-o-tag'),
            Stat::make('Şehirler', City::active()->count())
                ->color('success')
                ->icon('heroicon-o-map-pin'),
            Stat::make('Toplam Görüntülenme', number_format(Article::sum('view_count')))
                ->color('warning')
                ->icon('heroicon-o-eye'),
        ];
    }
}
