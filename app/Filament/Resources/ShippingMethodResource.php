<?php

namespace App\Filament\Resources;

use App\Enums\ShippingMode;
use App\Filament\Resources\ShippingMethodResource\Pages;
use App\Filament\Support\FormFields;
use App\Models\ShippingMethod;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ShippingMethodResource extends Resource
{
    protected static ?string $model = ShippingMethod::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationGroup = 'System';

    protected static ?string $modelLabel = 'Versandart';

    protected static ?string $pluralModelLabel = 'Versandarten';

    protected static ?string $navigationLabel = 'Versandarten';

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->columns(2)->schema([
                Forms\Components\TextInput::make('name')->label('Bezeichnung')->required()->maxLength(120),
                Forms\Components\TextInput::make('code')->label('Code')->required()->maxLength(40)->unique(ignoreRecord: true)
                    ->rule('regex:/^[a-z0-9_]+$/')->helperText('Technischer Schlüssel, z. B. dhl_standard. Nicht ändern, wenn bereits Bestellungen existieren.')
                    ->disabledOn('edit'),
                Forms\Components\TextInput::make('description')->label('Beschreibung im Checkout')->maxLength(255)->columnSpanFull(),
                Forms\Components\TextInput::make('carrier')->label('Versanddienstleister')->maxLength(40)->datalist(['DHL', 'DPD', 'UPS', 'GLS', 'Hermes']),
                Forms\Components\CheckboxList::make('allowed_shipping_modes')->label('Erlaubt für Versandmodus')
                    ->options(ShippingMode::class)->required()
                    ->helperText('Welche Ware (laut Rechtsklasse) mit dieser Versandart verschickt werden darf.'),
                FormFields::euro('price_gross', 'Preis (brutto)', true),
                FormFields::euro('free_from_gross', 'Versandkostenfrei ab (brutto)')->helperText('Leer = nie versandkostenfrei.'),
                Forms\Components\TextInput::make('sort_order')->label('Reihenfolge')->numeric()->default(0),
                Forms\Components\Toggle::make('is_active')->label('Aktiv')->default(true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Bezeichnung')->searchable(),
                Tables\Columns\TextColumn::make('code')->label('Code')->fontFamily('mono')->toggleable(),
                Tables\Columns\TextColumn::make('carrier')->label('Dienstleister')->placeholder('–'),
                Tables\Columns\TextColumn::make('allowed_shipping_modes')->label('Modus')->badge()
                    ->formatStateUsing(fn ($state) => ShippingMode::tryFrom($state)?->label() ?? $state),
                Tables\Columns\TextColumn::make('price_gross')->label('Preis')->formatStateUsing(fn ($state) => \App\Support\Money::format((int) $state)),
                Tables\Columns\TextColumn::make('free_from_gross')->label('Frei ab')->formatStateUsing(fn ($state) => \App\Support\Money::format((int) $state))->placeholder('–'),
                Tables\Columns\ToggleColumn::make('is_active')->label('Aktiv'),
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListShippingMethods::route('/'),
            'create' => Pages\CreateShippingMethod::route('/create'),
            'edit' => Pages\EditShippingMethod::route('/{record}/edit'),
        ];
    }
}
