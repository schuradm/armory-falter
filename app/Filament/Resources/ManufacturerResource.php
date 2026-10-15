<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ManufacturerResource\Pages;
use App\Filament\Support\FaqRepeater;
use App\Filament\Support\SeoFields;
use App\Models\Manufacturer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ManufacturerResource extends Resource
{
    protected static ?string $model = Manufacturer::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationGroup = 'Katalog';

    protected static ?int $navigationSort = 30;

    protected static ?string $modelLabel = 'Hersteller';

    protected static ?string $pluralModelLabel = 'Hersteller';

    protected static ?string $navigationLabel = 'Hersteller';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withCount('products');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Tabs::make()->columnSpanFull()->tabs([
                Forms\Components\Tabs\Tab::make('Allgemein')->columns(2)->schema([
                    Forms\Components\TextInput::make('name')->label('Name')->required()->maxLength(120)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Get $get, Set $set, ?string $state, string $operation) {
                            if ($operation === 'create' && blank($get('slug'))) {
                                $set('slug', Str::slug((string) $state));
                            }
                        }),
                    Forms\Components\TextInput::make('slug')->label('URL-Kürzel')->required()->maxLength(140)->alphaDash()->unique(ignoreRecord: true),
                    Forms\Components\TextInput::make('country_code')->label('Land (ISO, z. B. DE, AT, US)')->length(2)
                        ->formatStateUsing(fn (?string $state) => $state)
                        ->dehydrateStateUsing(fn (?string $state) => $state ? strtoupper($state) : null),
                    Forms\Components\TextInput::make('founded_year')->label('Gegründet')->numeric()->integer()
                        ->minValue(1500)->maxValue((int) date('Y')),
                    Forms\Components\TextInput::make('sort_order')->label('Sortierung')->numeric()->integer()->default(0),
                    Forms\Components\Toggle::make('is_featured')->label('Top-Marke (Startseite / Mega-Menü)')->inline(false),
                    Forms\Components\FileUpload::make('logo_path')->label('Logo (SVG oder PNG, transparent)')
                        ->disk('public')->directory('brands')->visibility('public')->maxSize(2048)
                        ->acceptedFileTypes(['image/svg+xml', 'image/png', 'image/webp']),
                    Forms\Components\FileUpload::make('hero_image_path')->label('Titelbild Markenseite')->image()->imageEditor()
                        ->disk('public')->directory('brands')->visibility('public')->maxSize(6144),
                ]),
                Forms\Components\Tabs\Tab::make('Texte')->schema([
                    Forms\Components\Textarea::make('intro')->label('Einleitung')->rows(3),
                    Forms\Components\RichEditor::make('content')->label('Markentext')->disableToolbarButtons(['attachFiles']),
                ]),
                Forms\Components\Tabs\Tab::make('FAQ')->schema([FaqRepeater::make()]),
                Forms\Components\Tabs\Tab::make('SEO')->schema([SeoFields::make()]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                Tables\Columns\ImageColumn::make('logo_path')->label('')->disk('public')->height(32),
                Tables\Columns\TextColumn::make('name')->label('Name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('country_code')->label('Land')->sortable(),
                Tables\Columns\TextColumn::make('products_count')->label('Produkte')->numeric()->sortable()->alignEnd(),
                Tables\Columns\ToggleColumn::make('is_featured')->label('Top-Marke'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_featured')->label('Top-Marke'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()->hidden(fn (Manufacturer $record) => $record->products_count > 0),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListManufacturers::route('/'),
            'create' => Pages\CreateManufacturer::route('/create'),
            'edit' => Pages\EditManufacturer::route('/{record}/edit'),
        ];
    }
}
