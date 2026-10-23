<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\RequiresPermission;
use App\Filament\Resources\TestimonialResource\Pages;
use App\Models\Testimonial;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TestimonialResource extends Resource
{
    use RequiresPermission;

    protected static ?string $model = Testimonial::class;

    protected static ?string $navigationIcon = 'heroicon-o-star';

    protected static ?string $navigationGroup = 'Marketing';

    protected static ?string $modelLabel = 'Kundenstimme';

    protected static ?string $pluralModelLabel = 'Kundenstimmen';

    protected static ?string $navigationLabel = 'Kundenstimmen';

    protected static ?int $navigationSort = 50;

    public const SOURCES = ['google' => 'Google', 'shop' => 'Shop', 'facebook' => 'Facebook', 'persoenlich' => 'Persönlich'];

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->columns(2)->schema([
                Forms\Components\TextInput::make('author_name')->label('Name')->required()->maxLength(120)->helperText('Nur mit Einverständnis, ggf. abgekürzt (z. B. „Thomas K.“).'),
                Forms\Components\TextInput::make('author_meta')->label('Zusatz')->maxLength(120)->placeholder('z. B. Jäger aus Esslingen'),
                Forms\Components\Textarea::make('body')->label('Text')->required()->rows(4)->columnSpanFull()
                    ->helperText('Nur echte Bewertungen wörtlich übernehmen (UWG).'),
                Forms\Components\Select::make('rating')->label('Bewertung')->options([5 => '★★★★★ (5)', 4 => '★★★★ (4)', 3 => '★★★ (3)', 2 => '★★ (2)', 1 => '★ (1)'])->default(5)->required(),
                Forms\Components\Select::make('source')->label('Quelle')->options(self::SOURCES),
                Forms\Components\TextInput::make('sort_order')->label('Reihenfolge')->numeric()->default(0),
                Forms\Components\Toggle::make('is_published')->label('Veröffentlicht'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('author_name')->label('Name')->searchable()->description(fn (Testimonial $r) => $r->author_meta),
                Tables\Columns\TextColumn::make('body')->label('Text')->limit(80)->wrap(),
                Tables\Columns\TextColumn::make('rating')->label('Sterne')->formatStateUsing(fn ($state) => str_repeat('★', (int) $state)),
                Tables\Columns\TextColumn::make('source')->label('Quelle')->formatStateUsing(fn ($state) => self::SOURCES[$state] ?? $state)->placeholder('–'),
                Tables\Columns\ToggleColumn::make('is_published')->label('Online'),
            ])
            ->filters([Tables\Filters\TernaryFilter::make('is_published')->label('Veröffentlicht')])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTestimonials::route('/'),
            'create' => Pages\CreateTestimonial::route('/create'),
            'edit' => Pages\EditTestimonial::route('/{record}/edit'),
        ];
    }
}
