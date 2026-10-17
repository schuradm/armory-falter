<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DiscountCodeResource\Pages;
use App\Filament\Resources\DiscountCodeResource\RelationManagers;
use App\Filament\Support\Euro;
use App\Models\Category;
use App\Models\DiscountCode;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/** Rabattcodes (Prozent oder Festbetrag) mit Laufzeit, Limits und Einschränkung auf Kategorien/Produkte. */
class DiscountCodeResource extends Resource
{
    protected static ?string $model = DiscountCode::class;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';

    protected static ?string $navigationGroup = 'Marketing';

    protected static ?string $modelLabel = 'Rabattcode';

    protected static ?string $pluralModelLabel = 'Rabattcodes';

    protected static ?string $navigationLabel = 'Rabattcodes';

    protected static ?int $navigationSort = 30;

    protected static ?string $recordTitleAttribute = 'code';

    public static function form(Form $form): Form
    {
        $categories = fn () => Category::orderBy('path')->pluck('name', 'id')->all();
        $products = fn (string $search) => Product::where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%")->limit(50)->pluck('name', 'id')->all();
        $productLabels = fn (array $values) => Product::whereKey($values)->pluck('name', 'id')->all();

        return $form->schema([
            Forms\Components\Section::make('Code')->columns(2)->schema([
                Forms\Components\TextInput::make('code')->label('Code')->required()->maxLength(40)
                    ->unique(ignoreRecord: true)
                    ->rule('regex:/^[A-Za-z0-9\-_ ]+$/')->validationMessages(['regex' => 'Nur Buchstaben, Ziffern und Bindestriche.'])
                    ->helperText('Groß-/Kleinschreibung und Bindestriche spielen beim Einlösen keine Rolle.')
                    ->dehydrateStateUsing(fn ($state) => DiscountCode::normalize($state)),
                Forms\Components\TextInput::make('description')->label('Interne Beschreibung')->maxLength(255),
                Forms\Components\Toggle::make('is_active')->label('Aktiv')->default(true)->columnSpanFull(),
            ]),
            Forms\Components\Section::make('Rabatt')->columns(3)->schema([
                Forms\Components\Select::make('type')->label('Art')->required()->live()->default('percent')
                    ->options(['percent' => 'Prozent', 'fixed' => 'Fester Betrag']),
                Forms\Components\TextInput::make('percent')->label('Prozent')->numeric()->minValue(0.01)->maxValue(100)->suffix('%')
                    ->visible(fn (Get $get) => $get('type') === 'percent')->required(fn (Get $get) => $get('type') === 'percent'),
                Euro::input('amount_gross', 'Betrag (brutto)')
                    ->visible(fn (Get $get) => $get('type') === 'fixed')->required(fn (Get $get) => $get('type') === 'fixed'),
                Euro::input('min_order_gross', 'Mindestbestellwert')->helperText('Warenwert ohne Versand und ohne Geschenkgutscheine.'),
            ]),
            Forms\Components\Section::make('Gültigkeit und Limits')->columns(2)->schema([
                Forms\Components\DateTimePicker::make('starts_at')->label('Gültig ab')->seconds(false),
                Forms\Components\DateTimePicker::make('ends_at')->label('Gültig bis')->seconds(false)->after('starts_at'),
                Forms\Components\TextInput::make('usage_limit')->label('Einlösungen gesamt')->numeric()->integer()->minValue(1)->placeholder('unbegrenzt'),
                Forms\Components\TextInput::make('usage_limit_per_customer')->label('Einlösungen je Kunde')->numeric()->integer()->minValue(1)->placeholder('unbegrenzt'),
            ]),
            Forms\Components\Section::make('Geltungsbereich')->columns(2)
                ->description('Ohne Auswahl gilt der Code für alle Artikel. Geschenkgutscheine sind nie rabattfähig. Kategorien schließen ihre Unterkategorien ein.')
                ->schema([
                    Forms\Components\Toggle::make('applies_to_permit_items')->label('Auch für erlaubnispflichtige Waffen und Munition')->columnSpanFull()
                        ->helperText('Standard: aus. Erlaubnispflichtige Artikel (WBK, Munitionserwerb) bleiben dann vom Rabatt ausgenommen.'),
                    Forms\Components\Select::make('include_category_ids')->label('Nur für Kategorien')->multiple()->options($categories),
                    Forms\Components\Select::make('exclude_category_ids')->label('Ausgenommene Kategorien')->multiple()->options($categories),
                    Forms\Components\Select::make('include_product_ids')->label('Nur für Produkte')->multiple()->searchable()
                        ->getSearchResultsUsing($products)->getOptionLabelsUsing($productLabels),
                    Forms\Components\Select::make('exclude_product_ids')->label('Ausgenommene Produkte')->multiple()->searchable()
                        ->getSearchResultsUsing($products)->getOptionLabelsUsing($productLabels),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('code')->label('Code')->searchable()->weight('bold')->copyable()
                    ->description(fn (DiscountCode $r) => $r->description),
                Tables\Columns\TextColumn::make('type')->label('Rabatt')->getStateUsing(fn (DiscountCode $r) => $r->label()),
                Tables\Columns\TextColumn::make('ends_at')->label('Gültig')
                    ->getStateUsing(fn (DiscountCode $r) => ($r->starts_at ? 'ab '.$r->starts_at->format('d.m.Y').' ' : '').($r->ends_at ? 'bis '.$r->ends_at->format('d.m.Y') : ($r->starts_at ? '' : 'unbefristet'))),
                Tables\Columns\TextColumn::make('times_used')->label('Eingelöst')->sortable()
                    ->formatStateUsing(fn (DiscountCode $r) => $r->times_used.($r->usage_limit ? ' / '.$r->usage_limit : '')),
                Euro::column('redemptions_sum_amount_gross', 'Rabatt gesamt'),
                Tables\Columns\IconColumn::make('applies_to_permit_items')->label('WBK-Ware')->boolean()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_active')->label('Aktiv')->boolean(),
            ])
            ->modifyQueryUsing(fn ($query) => $query->withSum(['redemptions' => fn ($q) => $q->where('status', 'active')], 'amount_gross'))
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('Aktiv'),
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [RelationManagers\RedemptionsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDiscountCodes::route('/'),
            'create' => Pages\CreateDiscountCode::route('/create'),
            'edit' => Pages\EditDiscountCode::route('/{record}/edit'),
        ];
    }
}
