<?php

namespace App\Filament\Resources\TipSubmissionResource\Pages;

use App\Filament\Resources\ArticleResource;
use App\Filament\Resources\TipSubmissionResource;
use App\Models\Article;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Str;

class EditTipSubmission extends EditRecord
{
    protected static string $resource = TipSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('convertToArticle')
                ->label('Habere Dönüştür')
                ->icon('heroicon-o-newspaper')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Habere Dönüştür')
                ->modalDescription('Bu ihbar onaylanıp haber taslağı olarak oluşturulacak. Haber düzenleme sayfasına yönlendirileceksiniz.')
                ->action(function () {
                    $tip = $this->record;

                    $articleData = [
                        'title' => $tip->title,
                        'slug' => Str::slug($tip->title),
                        'body' => nl2br(e($tip->content)),
                        'excerpt' => Str::limit(strip_tags($tip->content), 200),
                        'city_id' => $tip->city_id,
                        'user_id' => auth()->id(),
                        'is_published' => false,
                        'published_at' => now(),
                    ];

                    if ($tip->image_url) {
                        $articleData['image_url'] = $tip->image_url;
                    }

                    $article = Article::create($articleData);

                    $tip->update(['status' => 'approved']);

                    return redirect(ArticleResource::getUrl('edit', ['record' => $article]));
                })
                ->visible(fn () => $this->record->status !== 'approved'),

            Actions\DeleteAction::make(),
        ];
    }
}
