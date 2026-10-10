<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WeaponBookEntryResource\Pages;
use App\Filament\Resources\WeaponBookEntryResource\RelationManagers;
use App\Models\WeaponBookEntry;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class WeaponBookEntryResource extends Resource
{
    protected static ?string $model = WeaponBookEntry::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('weapon_item_id')
                    ->relationship('weaponItem', 'id')
                    ->required(),
                Forms\Components\TextInput::make('direction')
                    ->required()
                    ->maxLength(3),
                Forms\Components\Textarea::make('counterparty')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('permit_reference')
                    ->maxLength(120),
                Forms\Components\Select::make('order_id')
                    ->relationship('order', 'id'),
                Forms\Components\DateTimePicker::make('nwr_reported_at'),
                Forms\Components\TextInput::make('nwr_reference')
                    ->maxLength(80),
                Forms\Components\TextInput::make('booked_by')
                    ->required()
                    ->numeric(),
                Forms\Components\DateTimePicker::make('booked_at')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('weaponItem.id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('direction')
                    ->searchable(),
                Tables\Columns\TextColumn::make('permit_reference')
                    ->searchable(),
                Tables\Columns\TextColumn::make('order.id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('nwr_reported_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('nwr_reference')
                    ->searchable(),
                Tables\Columns\TextColumn::make('booked_by')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('booked_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWeaponBookEntries::route('/'),
            'create' => Pages\CreateWeaponBookEntry::route('/create'),
            'edit' => Pages\EditWeaponBookEntry::route('/{record}/edit'),
        ];
    }
}
