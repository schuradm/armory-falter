<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Filament\Resources\OrderResource;
use App\Filament\Support\Euro;
use App\Models\Order;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class OrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'orders';

    protected static ?string $title = 'Bestellungen';

    protected static ?string $modelLabel = 'Bestellung';

    protected static ?string $pluralModelLabel = 'Bestellungen';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('placed_at', 'desc')
            ->recordUrl(fn (Order $record) => OrderResource::getUrl('view', ['record' => $record]))
            ->columns([
                Tables\Columns\TextColumn::make('order_number')->label('Bestellnr.')->weight('bold'),
                Tables\Columns\TextColumn::make('placed_at')->label('Datum')->dateTime('d.m.Y H:i'),
                Tables\Columns\TextColumn::make('status')->label('Status')->badge(),
                Tables\Columns\TextColumn::make('compliance_status')->label('Freigabe')->badge()->color('gray'),
                Euro::column('total_gross', 'Summe'),
            ])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}
