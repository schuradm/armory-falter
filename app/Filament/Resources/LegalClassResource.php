<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LegalClassResource\Pages;
use App\Filament\Resources\LegalClassResource\RelationManagers;
use App\Models\LegalClass;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LegalClassResource extends Resource
{
    protected static ?string $model = LegalClass::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('code')
                    ->required()
                    ->maxLength(40),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(120),
                Forms\Components\TextInput::make('min_age')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\Toggle::make('requires_entitlement')
                    ->required(),
                Forms\Components\TextInput::make('accepted_entitlements'),
                Forms\Components\TextInput::make('shipping_mode')
                    ->required()
                    ->maxLength(30),
                Forms\Components\TextInput::make('allowed_payment_codes')
                    ->required(),
                Forms\Components\Toggle::make('requires_serial')
                    ->required(),
                Forms\Components\Textarea::make('notice_text')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('min_age')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('requires_entitlement')
                    ->boolean(),
                Tables\Columns\TextColumn::make('shipping_mode')
                    ->searchable(),
                Tables\Columns\IconColumn::make('requires_serial')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            'index' => Pages\ListLegalClasses::route('/'),
            'create' => Pages\CreateLegalClass::route('/create'),
            'edit' => Pages\EditLegalClass::route('/{record}/edit'),
        ];
    }
}
