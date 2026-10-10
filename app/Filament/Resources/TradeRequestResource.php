<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TradeRequestResource\Pages;
use App\Filament\Resources\TradeRequestResource\RelationManagers;
use App\Models\TradeRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TradeRequestResource extends Resource
{
    protected static ?string $model = TradeRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('reference')
                    ->required()
                    ->maxLength(20),
                Forms\Components\TextInput::make('type')
                    ->required()
                    ->maxLength(20),
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'id'),
                Forms\Components\TextInput::make('contact')
                    ->required(),
                Forms\Components\TextInput::make('weapon_type')
                    ->maxLength(80),
                Forms\Components\Select::make('manufacturer_id')
                    ->relationship('manufacturer', 'name'),
                Forms\Components\TextInput::make('manufacturer_name')
                    ->maxLength(120),
                Forms\Components\TextInput::make('model')
                    ->required()
                    ->maxLength(160),
                Forms\Components\TextInput::make('caliber')
                    ->maxLength(80),
                Forms\Components\TextInput::make('condition_code')
                    ->required()
                    ->maxLength(20),
                Forms\Components\TextInput::make('desired_price')
                    ->numeric(),
                Forms\Components\Textarea::make('description')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('status')
                    ->required()
                    ->maxLength(20)
                    ->default('new'),
                Forms\Components\TextInput::make('offer_price')
                    ->numeric(),
                Forms\Components\TextInput::make('egun_listing_id')
                    ->maxLength(60),
                Forms\Components\TextInput::make('advisory_pdf_path')
                    ->maxLength(255),
                Forms\Components\TextInput::make('assigned_to')
                    ->numeric(),
                Forms\Components\DateTimePicker::make('privacy_accepted_at')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('weapon_type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('manufacturer.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('manufacturer_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('model')
                    ->searchable(),
                Tables\Columns\TextColumn::make('caliber')
                    ->searchable(),
                Tables\Columns\TextColumn::make('condition_code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('desired_price')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->searchable(),
                Tables\Columns\TextColumn::make('offer_price')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('egun_listing_id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('advisory_pdf_path')
                    ->searchable(),
                Tables\Columns\TextColumn::make('assigned_to')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('privacy_accepted_at')
                    ->dateTime()
                    ->sortable(),
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
            'index' => Pages\ListTradeRequests::route('/'),
            'create' => Pages\CreateTradeRequest::route('/create'),
            'edit' => Pages\EditTradeRequest::route('/{record}/edit'),
        ];
    }
}
