<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WorkshopServiceResource\Pages;
use App\Filament\Support\FormFields;
use App\Models\WorkshopService;
use App\Support\Money;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class WorkshopServiceResource extends Resource
{
    protected static ?string $model = WorkshopService::class;

    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?string $navigationGroup = 'Service';

    protected static ?string $modelLabel = 'Werkstatt-Leistung';

    protected static ?string $pluralModelLabel = 'Werkstatt-Leistungen';

    protected static ?string $navigationLabel = 'Werkstatt-Leistungen';

    protected static ?int $navigationSort = 30;

    public static function form(Form $form): Form
    {
        return $form->columns(3)->schema([
            Forms\Components\Section::make('Leistung')->columnSpan(['lg' => 2])->schema([
                FormFields::title('name', 'Bezeichnung', 'slug', 120),
                FormFields::slug('slug', 80, '/werkstatt/'),
                Forms\Components\TextInput::make('teaser')->label('Kurzbeschreibung')->maxLength(255),
                FormFields::content('description', 'Beschreibung', 'workshop'),
            ]),
            Forms\Components\Section::make('Preis & Buchung')->columnSpan(['lg' => 1])->schema([
                FormFields::euro('price_from_gross', 'Preis ab (brutto)')->helperText('Leer = Preis auf Anfrage.'),
                Forms\Components\TextInput::make('duration_minutes')->label('Dauer')->numeric()->minValue(15)->step(15)->default(30)->suffix('Min.')->required(),
                Forms\Components\Toggle::make('is_bookable')->label('Online buchbar')->default(true),
                Forms\Components\TextInput::make('sort_order')->label('Reihenfolge')->numeric()->default(0),
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
                Tables\Columns\TextColumn::make('price_from_gross')->label('Preis ab')->formatStateUsing(fn ($state) => Money::format((int) $state))->placeholder('auf Anfrage'),
                Tables\Columns\TextColumn::make('duration_minutes')->label('Dauer')->suffix(' Min.'),
                Tables\Columns\IconColumn::make('is_bookable')->label('Buchbar')->boolean(),
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWorkshopServices::route('/'),
            'create' => Pages\CreateWorkshopService::route('/create'),
            'edit' => Pages\EditWorkshopService::route('/{record}/edit'),
        ];
    }
}
