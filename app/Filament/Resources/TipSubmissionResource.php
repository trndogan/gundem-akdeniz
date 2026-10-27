<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TipSubmissionResource\Pages;
use App\Models\TipSubmission;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TipSubmissionResource extends Resource
{
    protected static ?string $model = TipSubmission::class;
    protected static ?string $navigationIcon = 'heroicon-o-envelope-open';
    protected static ?string $navigationGroup = 'İçerik';
    protected static ?string $navigationLabel = 'Haber İhbarları';
    protected static ?string $modelLabel = 'Haber İhbarı';
    protected static ?string $pluralModelLabel = 'Haber İhbarları';
    protected static ?int $navigationSort = 5;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Group::make()->schema([
                Forms\Components\Section::make('Gönderen Bilgileri')->schema([
                    Forms\Components\TextInput::make('name')->label('Ad Soyad')->disabled(),
                    Forms\Components\TextInput::make('email')->label('E-posta')->disabled(),
                    Forms\Components\TextInput::make('phone')->label('Telefon')->disabled(),
                    Forms\Components\TextInput::make('ip_address')->label('IP Adresi')->disabled(),
                ])->columns(2),
                Forms\Components\Section::make('Haber İçeriği')->schema([
                    Forms\Components\TextInput::make('title')->label('Başlık')->required(),
                    Forms\Components\Select::make('city_id')->label('İl')->relationship('city', 'name')->searchable()->preload(),
                    Forms\Components\Textarea::make('content')->label('İçerik')->required()->rows(8)->columnSpanFull(),
                    Forms\Components\Placeholder::make('image_preview')
                        ->label('Gönderilen Görsel')
                        ->content(fn ($record) => $record?->image_display_url
                            ? new \Illuminate\Support\HtmlString('<img src="' . e($record->image_display_url) . '" class="rounded-lg max-h-48 border border-gray-200" />')
                            : 'Görsel yüklenmemiş')
                        ->columnSpanFull(),
                ])->columns(2),
            ])->columnSpan(['lg' => 2]),
            Forms\Components\Group::make()->schema([
                Forms\Components\Section::make('Durum')->schema([
                    Forms\Components\Select::make('status')
                        ->label('Durum')
                        ->options([
                            'pending' => 'Beklemede',
                            'reviewed' => 'İncelendi',
                            'approved' => 'Onaylandı',
                            'rejected' => 'Reddedildi',
                        ])
                        ->required(),
                    Forms\Components\Textarea::make('admin_note')
                        ->label('Editör Notu')
                        ->rows(3)
                        ->placeholder('İç not...'),
                ]),
                Forms\Components\Section::make('Tarih')->schema([
                    Forms\Components\Placeholder::make('created_at')
                        ->label('Gönderilme')
                        ->content(fn ($record) => $record?->created_at?->translatedFormat('d F Y H:i')),
                ]),
            ])->columnSpan(['lg' => 1]),
        ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('#')->sortable(),
                Tables\Columns\TextColumn::make('name')->label('Gönderen')->searchable()->weight('bold'),
                Tables\Columns\TextColumn::make('title')->label('Başlık')->searchable()->limit(50),
                Tables\Columns\TextColumn::make('city.name')->label('İl')->badge(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Durum')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'pending' => 'Beklemede',
                        'reviewed' => 'İncelendi',
                        'approved' => 'Onaylandı',
                        'rejected' => 'Reddedildi',
                        default => $state,
                    })
                    ->color(fn ($state) => match ($state) {
                        'pending' => 'warning',
                        'reviewed' => 'info',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')->label('Tarih')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Durum')
                    ->options([
                        'pending' => 'Beklemede',
                        'reviewed' => 'İncelendi',
                        'approved' => 'Onaylandı',
                        'rejected' => 'Reddedildi',
                    ]),
                Tables\Filters\SelectFilter::make('city_id')
                    ->label('İl')
                    ->relationship('city', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTipSubmissions::route('/'),
            'edit' => Pages\EditTipSubmission::route('/{record}/edit'),
        ];
    }
}
