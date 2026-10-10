<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('sku')
                    ->label('SKU')
                    ->required()
                    ->maxLength(40),
                Forms\Components\TextInput::make('ean')
                    ->maxLength(14),
                Forms\Components\TextInput::make('manufacturer_part_number')
                    ->maxLength(60),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(200),
                Forms\Components\TextInput::make('slug')
                    ->required()
                    ->maxLength(220),
                Forms\Components\Select::make('manufacturer_id')
                    ->relationship('manufacturer', 'name'),
                Forms\Components\Select::make('caliber_id')
                    ->relationship('caliber', 'name'),
                Forms\Components\Select::make('legal_class_id')
                    ->relationship('legalClass', 'name')
                    ->required(),
                Forms\Components\Select::make('tax_rate_id')
                    ->relationship('taxRate', 'name')
                    ->required(),
                Forms\Components\TextInput::make('product_type')
                    ->required()
                    ->maxLength(20),
                Forms\Components\TextInput::make('weapon_kind')
                    ->maxLength(20),
                Forms\Components\TextInput::make('condition_code')
                    ->required()
                    ->maxLength(20)
                    ->default('new'),
                Forms\Components\Textarea::make('condition_notes')
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('is_unique_item')
                    ->required(),
                Forms\Components\Toggle::make('usage_hunting')
                    ->required(),
                Forms\Components\Toggle::make('usage_sport')
                    ->required(),
                Forms\Components\Toggle::make('authority_only')
                    ->required(),
                Forms\Components\TextInput::make('price_gross')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('special_price_gross')
                    ->numeric(),
                Forms\Components\DateTimePicker::make('special_from'),
                Forms\Components\DateTimePicker::make('special_to'),
                Forms\Components\TextInput::make('lowest_price_30d')
                    ->numeric(),
                Forms\Components\TextInput::make('uvp_gross')
                    ->numeric(),
                Forms\Components\TextInput::make('stock_quantity')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('stock_reserved')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('availability')
                    ->required()
                    ->maxLength(20)
                    ->default('in_stock'),
                Forms\Components\TextInput::make('delivery_time')
                    ->maxLength(40),
                Forms\Components\TextInput::make('weight_grams')
                    ->numeric(),
                Forms\Components\Textarea::make('short_description')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('description')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('video_url')
                    ->maxLength(255),
                Forms\Components\Toggle::make('is_featured')
                    ->required(),
                Forms\Components\TextInput::make('status')
                    ->required()
                    ->maxLength(20)
                    ->default('draft'),
                Forms\Components\DateTimePicker::make('published_at'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable(),
                Tables\Columns\TextColumn::make('ean')
                    ->searchable(),
                Tables\Columns\TextColumn::make('manufacturer_part_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('slug')
                    ->searchable(),
                Tables\Columns\TextColumn::make('manufacturer.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('caliber.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('legalClass.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('taxRate.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('product_type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('weapon_kind')
                    ->searchable(),
                Tables\Columns\TextColumn::make('condition_code')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_unique_item')
                    ->boolean(),
                Tables\Columns\IconColumn::make('usage_hunting')
                    ->boolean(),
                Tables\Columns\IconColumn::make('usage_sport')
                    ->boolean(),
                Tables\Columns\IconColumn::make('authority_only')
                    ->boolean(),
                Tables\Columns\TextColumn::make('price_gross')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('special_price_gross')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('special_from')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('special_to')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('lowest_price_30d')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('uvp_gross')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('stock_quantity')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('stock_reserved')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('availability')
                    ->searchable(),
                Tables\Columns\TextColumn::make('delivery_time')
                    ->searchable(),
                Tables\Columns\TextColumn::make('weight_grams')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('video_url')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_featured')
                    ->boolean(),
                Tables\Columns\TextColumn::make('status')
                    ->searchable(),
                Tables\Columns\TextColumn::make('published_at')
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
