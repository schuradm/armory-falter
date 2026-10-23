<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\RequiresPermission;
use App\Filament\Resources\TaxRateResource\Pages;
use App\Models\TaxRate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TaxRateResource extends Resource
{
    use RequiresPermission;

    protected static ?string $model = TaxRate::class;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';

    protected static ?string $navigationGroup = 'System';

    protected static ?string $modelLabel = 'Steuersatz';

    protected static ?string $pluralModelLabel = 'Steuersätze';

    protected static ?string $navigationLabel = 'Steuersätze';

    protected static ?int $navigationSort = 20;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->columns(2)->schema([
                Forms\Components\TextInput::make('name')->label('Bezeichnung')->required()->maxLength(40)->placeholder('z. B. Regelsteuersatz'),
                Forms\Components\TextInput::make('rate')->label('Steuersatz')->required()->numeric()->minValue(0)->maxValue(100)->step(0.01)->suffix('%'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Bezeichnung')->searchable(),
                Tables\Columns\TextColumn::make('rate')->label('Satz')->suffix(' %')->sortable(),
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTaxRates::route('/'),
            'create' => Pages\CreateTaxRate::route('/create'),
            'edit' => Pages\EditTaxRate::route('/{record}/edit'),
        ];
    }
}
