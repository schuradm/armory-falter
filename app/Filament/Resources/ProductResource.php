<?php

namespace App\Filament\Resources;

use App\Enums\Availability;
use App\Enums\Condition;
use App\Enums\ProductType;
use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Support\Euro;
use App\Filament\Support\SeoFields;
use App\Models\AttributeDefinition;
use App\Models\Category;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationGroup = 'Katalog';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'Produkt';

    protected static ?string $pluralModelLabel = 'Produkte';

    protected static ?string $navigationLabel = 'Produkte';

    protected static ?string $recordTitleAttribute = 'name';

    public const STATUSES = ['draft' => 'Entwurf', 'active' => 'Aktiv (sichtbar)', 'archived' => 'Archiviert'];

    public const WEAPON_KINDS = ['kurzwaffe' => 'Kurzwaffe', 'langwaffe' => 'Langwaffe'];

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'sku', 'ean', 'manufacturer_part_number'];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['manufacturer', 'legalClass', 'media']);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Tabs::make('Produkt')->persistTabInQueryString()->columnSpanFull()->tabs([
                Forms\Components\Tabs\Tab::make('Basis')->icon('heroicon-o-information-circle')->schema([
                    Forms\Components\Section::make()->columns(2)->schema([
                        Forms\Components\TextInput::make('name')->label('Name')->required()->maxLength(200)->columnSpanFull()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Get $get, Set $set, ?string $state, string $operation) {
                                if ($operation === 'create' && blank($get('slug'))) {
                                    $set('slug', Str::slug((string) $state));
                                }
                            }),
                        Forms\Components\TextInput::make('slug')->label('URL-Kürzel')->required()->maxLength(220)
                            ->unique(ignoreRecord: true)->alphaDash()
                            ->helperText('Teil der Produkt-URL. Nach Veröffentlichung nicht mehr ändern (sonst Weiterleitung anlegen).'),
                        Forms\Components\TextInput::make('sku')->label('Artikelnummer (SKU)')->required()->maxLength(40)->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('ean')->label('EAN / GTIN')->maxLength(14),
                        Forms\Components\TextInput::make('manufacturer_part_number')->label('Herstellernummer')->maxLength(60),
                        Forms\Components\Select::make('manufacturer_id')->label('Hersteller')
                            ->relationship('manufacturer', 'name')->searchable()->preload(),
                        Forms\Components\Select::make('product_type')->label('Produktart')->options(ProductType::class)
                            ->required()->live()->native(false),
                        Forms\Components\Select::make('weapon_kind')->label('Waffenart')->options(self::WEAPON_KINDS)
                            ->visible(fn (Get $get) => self::isType($get('product_type'), ProductType::Weapon))
                            ->required(fn (Get $get) => self::isType($get('product_type'), ProductType::Weapon)),
                        Forms\Components\Select::make('caliber_id')->label('Kaliber')
                            ->relationship('caliber', 'name')->searchable()->preload(),
                        Forms\Components\Select::make('legal_class_id')->label('Rechtsklasse')
                            ->relationship('legalClass', 'name')->required()->preload()
                            ->helperText('Bestimmt Altersgrenze, nötige Erwerbsberechtigung, Versand und erlaubte Zahlarten.'),
                    ]),
                    Forms\Components\Section::make('Kategorien')->columns(2)->schema([
                        Forms\Components\Select::make('categories')->label('Kategorien')
                            ->relationship('categories', 'name')->multiple()->preload()->searchable()->live()
                            ->getOptionLabelFromRecordUsing(fn (Category $record) => $record->path ? str_replace('/', ' › ', $record->path) : $record->name),
                        Forms\Components\Select::make('primary_category_id')->label('Hauptkategorie')
                            ->helperText('Für Breadcrumbs und Canonical. Leer = erste Kategorie.')
                            ->options(fn (Get $get) => Category::whereIn('id', (array) $get('categories'))->pluck('name', 'id'))
                            ->dehydrated(false),
                    ]),
                    Forms\Components\Section::make('Zustand und Verwendung')->columns(4)->schema([
                        Forms\Components\Select::make('condition_code')->label('Zustand')->options(Condition::class)
                            ->default(Condition::New->value)->required()->live()->columnSpan(2),
                        Forms\Components\Textarea::make('condition_notes')->label('Zustandsbeschreibung')->rows(2)->columnSpan(2)
                            ->visible(fn (Get $get) => $get('condition_code') && $get('condition_code') !== Condition::New->value && $get('condition_code') !== Condition::New),
                        Forms\Components\Toggle::make('usage_hunting')->label('Jagd'),
                        Forms\Components\Toggle::make('usage_sport')->label('Sport'),
                        Forms\Components\Toggle::make('is_unique_item')->label('Einzelstück')
                            ->helperText('Gebrauchtwaffe o. Ä.: nach Verkauf automatisch ausverkauft.'),
                        Forms\Components\Toggle::make('authority_only')->label('Nur Behörden/Händler'),
                    ]),
                    Forms\Components\Section::make('Texte')->schema([
                        Forms\Components\Textarea::make('short_description')->label('Kurzbeschreibung')->rows(3)->maxLength(500)
                            ->helperText('Erscheint neben dem Kaufbutton und in Listen (Attention/Interest).'),
                        Forms\Components\RichEditor::make('description')->label('Beschreibung')
                            ->disableToolbarButtons(['attachFiles'])->columnSpanFull(),
                        Forms\Components\TextInput::make('video_url')->label('Video-URL (YouTube/Vimeo)')->url()->maxLength(255),
                    ]),
                    Forms\Components\Section::make('Veröffentlichung')->columns(3)->schema([
                        Forms\Components\Select::make('status')->label('Status')->options(self::STATUSES)->default('draft')->required(),
                        Forms\Components\DateTimePicker::make('published_at')->label('Veröffentlicht am')->native(false)->seconds(false),
                        Forms\Components\Toggle::make('is_featured')->label('Hervorgehoben (Startseite)')->inline(false),
                    ]),
                ]),

                Forms\Components\Tabs\Tab::make('Preise')->icon('heroicon-o-currency-euro')->schema([
                    Forms\Components\Section::make()->columns(2)->schema([
                        Euro::input('price_gross', 'Verkaufspreis brutto')->required(),
                        Forms\Components\Select::make('tax_rate_id')->label('Steuersatz')
                            ->relationship('taxRate', 'name')->required()->preload(),
                        Euro::input('uvp_gross', 'UVP des Herstellers (brutto)'),
                        Euro::input('lowest_price_30d', 'Niedrigster Preis der letzten 30 Tage')
                            ->helperText('Pflicht für Streichpreise nach § 11 PAngV. Leer = aktueller Preis.'),
                    ]),
                    Forms\Components\Section::make('Aktionspreis')->columns(3)->schema([
                        Euro::input('special_price_gross', 'Aktionspreis brutto')
                            ->helperText('Muss unter dem Verkaufspreis liegen.'),
                        Forms\Components\DateTimePicker::make('special_from')->label('Gültig ab')->native(false)->seconds(false),
                        Forms\Components\DateTimePicker::make('special_to')->label('Gültig bis')->native(false)->seconds(false)
                            ->after('special_from'),
                    ]),
                ]),

                Forms\Components\Tabs\Tab::make('Lager')->icon('heroicon-o-archive-box')->schema([
                    Forms\Components\Section::make()->columns(3)->schema([
                        Forms\Components\TextInput::make('stock_quantity')->label('Lagerbestand')->numeric()->integer()
                            ->default(0)->required(),
                        Forms\Components\TextInput::make('stock_reserved')->label('Reserviert (offene Bestellungen)')
                            ->numeric()->disabled()->dehydrated(false),
                        Forms\Components\Select::make('availability')->label('Verfügbarkeit')->options(Availability::class)
                            ->default(Availability::InStock->value)->required(),
                        Forms\Components\TextInput::make('delivery_time')->label('Lieferzeit')->maxLength(40)
                            ->placeholder('z. B. 2–4 Werktage'),
                        Forms\Components\TextInput::make('weight_grams')->label('Gewicht')->numeric()->integer()->suffix('g'),
                    ]),
                ]),

                Forms\Components\Tabs\Tab::make('Medien')->icon('heroicon-o-photo')->schema([
                    Forms\Components\Repeater::make('media')->label('Bilder')
                        ->relationship('media')
                        ->orderColumn('sort_order')
                        ->reorderable()->collapsible()->cloneable(false)
                        ->grid(2)
                        ->defaultItems(0)
                        ->addActionLabel('Bild hinzufügen')
                        ->helperText('Das erste Bild ist das Hauptbild. WebP-Varianten werden automatisch im Hintergrund erzeugt.')
                        ->itemLabel(fn (array $state): ?string => $state['alt_text'] ?? null)
                        ->schema([
                            Forms\Components\Hidden::make('type')->default('image'),
                            Forms\Components\FileUpload::make('path')->label('Bild')->required()
                                ->image()->imageEditor()
                                ->disk('public')->directory('products')->visibility('public')
                                ->maxSize(8192)
                                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp']),
                            Forms\Components\TextInput::make('alt_text')->label('Alternativtext')->required()->maxLength(255)
                                ->helperText('Beschreibt das Bild für Screenreader und Google.'),
                        ]),
                ]),

                Forms\Components\Tabs\Tab::make('Merkmale')->icon('heroicon-o-list-bullet')->schema([
                    Forms\Components\Repeater::make('attribute_values')->label('Technische Daten')
                        ->dehydrated(false)
                        ->defaultItems(0)
                        ->reorderable(false)
                        ->addActionLabel('Merkmal hinzufügen')
                        ->columns(3)
                        ->schema([
                            Forms\Components\Select::make('attribute_definition_id')->label('Merkmal')->required()
                                ->options(fn () => AttributeDefinition::orderBy('sort_order')->orderBy('name')->get()
                                    ->mapWithKeys(fn ($a) => [$a->id => $a->name.($a->unit ? " ({$a->unit})" : '')]))
                                ->searchable()
                                ->distinct()
                                ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                ->createOptionForm([
                                    Forms\Components\TextInput::make('name')->label('Bezeichnung')->required()->maxLength(120),
                                    Forms\Components\TextInput::make('code')->label('Code')->required()->maxLength(60)->alphaDash()
                                        ->unique(AttributeDefinition::class, 'code'),
                                    Forms\Components\TextInput::make('unit')->label('Einheit')->maxLength(20)->placeholder('mm, g, Schuss'),
                                    Forms\Components\Select::make('data_type')->label('Datentyp')->default('text')->required()
                                        ->options(['text' => 'Text', 'number' => 'Zahl']),
                                    Forms\Components\TextInput::make('group_name')->label('Gruppe')->maxLength(80),
                                    Forms\Components\Toggle::make('is_filterable')->label('Als Filter im Shop anbieten'),
                                ])
                                ->createOptionUsing(fn (array $data) => AttributeDefinition::create($data)->getKey()),
                            Forms\Components\TextInput::make('value_text')->label('Wert (Text)')->maxLength(255),
                            Forms\Components\TextInput::make('value_number')->label('Wert (Zahl, für Filter)')->numeric(),
                        ]),
                ]),

                Forms\Components\Tabs\Tab::make('Zubehör')->icon('heroicon-o-puzzle-piece')->schema([
                    Forms\Components\Repeater::make('accessory_ids')->label('„Passend dazu“ (Upsell im Produkt und im Warenkorb)')
                        ->dehydrated(false)
                        ->simple(
                            Forms\Components\Select::make('related_product_id')->label('Produkt')->required()
                                ->searchable()
                                ->distinct()
                                ->getSearchResultsUsing(fn (string $search, ?Model $record) => Product::query()
                                    ->when($record, fn ($q) => $q->whereKeyNot($record->getKey()))
                                    ->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%"))
                                    ->limit(50)->get()->mapWithKeys(fn (Product $p) => [$p->id => "{$p->name} ({$p->sku})"]))
                                ->getOptionLabelUsing(fn ($value) => ($p = Product::find($value)) ? "{$p->name} ({$p->sku})" : null),
                        )
                        ->defaultItems(0)
                        ->reorderable()
                        ->addActionLabel('Zubehör hinzufügen')
                        ->helperText('Reihenfolge = Anzeigereihenfolge. Empfehlung: 3–6 Artikel.'),
                ]),

                Forms\Components\Tabs\Tab::make('SEO')->icon('heroicon-o-magnifying-glass')->schema([
                    Forms\Components\Section::make('Suchmaschinen')->schema([SeoFields::make()]),
                    Forms\Components\Section::make('Häufige Fragen zum Produkt')->schema([
                        Forms\Components\Repeater::make('faqs')->hiddenLabel()
                            ->relationship('faqs')
                            ->orderColumn('sort_order')
                            ->defaultItems(0)->collapsible()
                            ->addActionLabel('Frage hinzufügen')
                            ->itemLabel(fn (array $state): ?string => $state['question'] ?? null)
                            ->schema([
                                Forms\Components\TextInput::make('question')->label('Frage')->required()->maxLength(300),
                                Forms\Components\Textarea::make('answer')->label('Antwort')->required()->rows(3),
                            ]),
                    ]),
                ]),
            ]),
        ]);
    }

    private static function isType(mixed $state, ProductType $type): bool
    {
        return $state === $type || $state === $type->value;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                Tables\Columns\ImageColumn::make('image')->label('')
                    ->getStateUsing(fn (Product $record) => $record->media->firstWhere('type', 'image')?->url('thumb'))
                    ->square()->size(48),
                Tables\Columns\TextColumn::make('name')->label('Name')->searchable()->sortable()->wrap()
                    ->description(fn (Product $record) => $record->manufacturer?->name),
                Tables\Columns\TextColumn::make('sku')->label('SKU')->searchable()->sortable()->copyable()->toggleable(),
                Tables\Columns\TextColumn::make('product_type')->label('Art')->badge()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('legalClass.name')->label('Rechtsklasse')->badge()->color('gray')->toggleable(),
                Euro::column('price_gross', 'Preis')->sortable()
                    ->description(fn (Product $record) => $record->hasActiveSpecialPrice()
                        ? 'Aktion: '.\App\Support\Money::format($record->special_price_gross) : null),
                Tables\Columns\TextColumn::make('stock_quantity')->label('Bestand')->numeric()->sortable()->alignEnd()
                    ->color(fn (Product $record) => $record->stock_quantity <= 0 ? 'danger' : ($record->stock_quantity <= 2 ? 'warning' : null)),
                Tables\Columns\TextColumn::make('availability')->label('Verfügbarkeit')->badge()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\ToggleColumn::make('is_active')->label('Aktiv')
                    ->getStateUsing(fn (Product $record) => $record->status === 'active')
                    ->updateStateUsing(function (Product $record, bool $state) {
                        $record->update([
                            'status' => $state ? 'active' : 'draft',
                            'published_at' => $state ? ($record->published_at ?? now()) : $record->published_at,
                        ]);

                        return $state;
                    }),
                Tables\Columns\IconColumn::make('is_featured')->label('Top')->boolean()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')->label('Geändert')->since()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('categories')->label('Kategorie')
                    ->relationship('categories', 'name')->multiple()->preload(),
                Tables\Filters\SelectFilter::make('manufacturer_id')->label('Hersteller')
                    ->relationship('manufacturer', 'name')->multiple()->preload(),
                Tables\Filters\SelectFilter::make('legal_class_id')->label('Rechtsklasse')
                    ->relationship('legalClass', 'name')->multiple()->preload(),
                Tables\Filters\SelectFilter::make('product_type')->label('Produktart')->options(ProductType::class),
                Tables\Filters\SelectFilter::make('status')->label('Status')->options(self::STATUSES),
                Tables\Filters\Filter::make('low_stock')->label('Bestand ≤ 2')
                    ->query(fn (Builder $query) => $query->where('stock_quantity', '<=', 2)),
            ])
            ->actions([
                Tables\Actions\Action::make('shop')->label('Im Shop')->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Product $record) => route('product.show', $record))->openUrlInNewTab()
                    ->visible(fn (Product $record) => $record->status === 'active'),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate')->label('Aktivieren')->icon('heroicon-o-eye')
                        ->action(fn ($records) => $records->each->update(['status' => 'active']))->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('deactivate')->label('Deaktivieren')->icon('heroicon-o-eye-slash')
                        ->action(fn ($records) => $records->each->update(['status' => 'draft']))->deselectRecordsAfterCompletion(),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
