<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('order_number')
                    ->required()
                    ->maxLength(20),
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'id')
                    ->required(),
                Forms\Components\TextInput::make('status')
                    ->required()
                    ->maxLength(30),
                Forms\Components\TextInput::make('compliance_status')
                    ->required()
                    ->maxLength(20),
                Forms\Components\TextInput::make('billing_address')
                    ->required(),
                Forms\Components\TextInput::make('shipping_address'),
                Forms\Components\TextInput::make('shipping_method_code')
                    ->required()
                    ->maxLength(40),
                Forms\Components\TextInput::make('payment_method_code')
                    ->required()
                    ->maxLength(40),
                Forms\Components\TextInput::make('subtotal_gross')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('shipping_gross')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('discount_gross')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('total_gross')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('total_tax')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('currency')
                    ->required()
                    ->maxLength(3)
                    ->default('EUR'),
                Forms\Components\Textarea::make('customer_note')
                    ->columnSpanFull(),
                Forms\Components\DateTimePicker::make('terms_accepted_at')
                    ->required(),
                Forms\Components\DateTimePicker::make('legal_declaration_accepted_at'),
                Forms\Components\TextInput::make('ip_address')
                    ->maxLength(45),
                Forms\Components\TextInput::make('idempotency_key')
                    ->required()
                    ->maxLength(36),
                Forms\Components\DateTimePicker::make('placed_at')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->searchable(),
                Tables\Columns\TextColumn::make('compliance_status')
                    ->searchable(),
                Tables\Columns\TextColumn::make('shipping_method_code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('payment_method_code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('subtotal_gross')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('shipping_gross')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('discount_gross')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_gross')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_tax')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('currency')
                    ->searchable(),
                Tables\Columns\TextColumn::make('terms_accepted_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('legal_declaration_accepted_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ip_address')
                    ->searchable(),
                Tables\Columns\TextColumn::make('idempotency_key')
                    ->searchable(),
                Tables\Columns\TextColumn::make('placed_at')
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
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
