<?php

namespace App\Filament\Resources;

use App\Enums\EntitlementType;
use App\Enums\PaymentMethod;
use App\Enums\ShippingMode;
use App\Filament\Concerns\RequiresPermission;
use App\Filament\Resources\LegalClassResource\Pages;
use App\Models\LegalClass;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Rechtsklassen steuern Altersgrenze, Erwerbsberechtigung, Versand und Zahlarten.
 * Änderungen wirken sofort auf Warenkorb und Checkout – nur nach anwaltlicher Prüfung ändern.
 */
class LegalClassResource extends Resource
{
    use RequiresPermission;

    protected static ?string $model = LegalClass::class;

    protected static ?string $navigationIcon = 'heroicon-o-scale';

    protected static ?string $navigationGroup = 'Katalog';

    protected static ?int $navigationSort = 50;

    protected static ?string $modelLabel = 'Rechtsklasse';

    protected static ?string $pluralModelLabel = 'Rechtsklassen';

    protected static ?string $navigationLabel = 'Rechtsklassen';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withCount('products');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()
                ->description('Achtung: Änderungen wirken sofort auf Warenkorb, Versand- und Zahlartenauswahl. Werte vor Go-live anwaltlich bestätigen lassen.')
                ->columns(2)->schema([
                    Forms\Components\TextInput::make('name')->label('Bezeichnung')->required()->maxLength(120),
                    Forms\Components\TextInput::make('code')->label('Code')->required()->maxLength(40)->alphaDash()->unique(ignoreRecord: true)
                        ->disabledOn('edit')
                        ->helperText('Technischer Schlüssel, wird in Bestellpositionen gespeichert.'),
                    Forms\Components\TextInput::make('min_age')->label('Mindestalter')->numeric()->integer()->minValue(0)->maxValue(25)
                        ->default(0)->suffix('Jahre')->required(),
                    Forms\Components\Select::make('shipping_mode')->label('Versandart (mindestens)')->options(ShippingMode::class)->required(),
                    Forms\Components\Toggle::make('requires_entitlement')->label('Erwerbsberechtigung erforderlich')->live(),
                    Forms\Components\Toggle::make('requires_serial')->label('Seriennummer erforderlich (Waffenbuch)'),
                ]),
            Forms\Components\Section::make('Akzeptierte Erwerbsberechtigungen')
                ->visible(fn (Forms\Get $get) => (bool) $get('requires_entitlement'))
                ->schema([
                    Forms\Components\CheckboxList::make('accepted_entitlements')->hiddenLabel()
                        ->options(EntitlementType::class)->columns(2)->bulkToggleable(),
                ]),
            Forms\Components\Section::make('Erlaubte Zahlarten')
                ->description('PayPal, Klarna/Sofort und Kreditkarte sind laut Anbieter-AGB nur für frei verkäufliche Ware zulässig.')
                ->schema([
                    Forms\Components\CheckboxList::make('allowed_payment_codes')->hiddenLabel()
                        ->options(PaymentMethod::selectableOptions())->columns(2)->required()->default([]),
                ]),
            Forms\Components\Section::make('Hinweis im Shop')->schema([
                Forms\Components\Textarea::make('notice_text')->hiddenLabel()->rows(3)
                    ->helperText('Wird auf der Produktseite und im Warenkorb angezeigt, z. B. „Abgabe nur gegen Vorlage der WBK mit Voreintrag“.'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('min_age')
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Bezeichnung')->searchable()->sortable()
                    ->description(fn (LegalClass $record) => $record->code),
                Tables\Columns\TextColumn::make('min_age')->label('Ab')->suffix(' J.')->sortable(),
                Tables\Columns\IconColumn::make('requires_entitlement')->label('Erlaubnis')->boolean(),
                Tables\Columns\TextColumn::make('shipping_mode')->label('Versand')->wrap(),
                Tables\Columns\TextColumn::make('allowed_payment_codes')->label('Zahlarten')->badge()->color('gray')
                    ->formatStateUsing(fn ($state) => PaymentMethod::tryFrom((string) $state)?->label() ?? $state),
                Tables\Columns\TextColumn::make('products_count')->label('Produkte')->numeric()->alignEnd(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()->hidden(fn (LegalClass $record) => $record->products_count > 0),
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
            'index' => Pages\ListLegalClasses::route('/'),
            'create' => Pages\CreateLegalClass::route('/create'),
            'edit' => Pages\EditLegalClass::route('/{record}/edit'),
        ];
    }
}
