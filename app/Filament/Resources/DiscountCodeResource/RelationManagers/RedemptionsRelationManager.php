<?php

namespace App\Filament\Resources\DiscountCodeResource\RelationManagers;

use App\Filament\Resources\OrderResource;
use App\Filament\Support\Euro;
use App\Models\DiscountCodeRedemption;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class RedemptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'redemptions';

    protected static ?string $title = 'Einlösungen';

    protected static ?string $modelLabel = 'Einlösung';

    protected static ?string $pluralModelLabel = 'Einlösungen';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->with(['order:id,order_number', 'user:id,first_name,last_name,email']))
            ->recordUrl(fn (DiscountCodeRedemption $r) => $r->order ? OrderResource::getUrl('view', ['record' => $r->order]) : null)
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->label('Datum')->dateTime('d.m.Y H:i'),
                Tables\Columns\TextColumn::make('order.order_number')->label('Bestellung')->weight('bold'),
                Tables\Columns\TextColumn::make('user.email')->label('Kunde'),
                Euro::column('amount_gross', 'Rabatt'),
                Tables\Columns\TextColumn::make('status')->label('Status')->badge()
                    ->formatStateUsing(fn (string $state) => $state === 'active' ? 'Eingelöst' : 'Freigegeben (Storno)')
                    ->color(fn (string $state) => $state === 'active' ? 'success' : 'gray'),
            ])
            ->headerActions([])->actions([])->bulkActions([]);
    }
}
