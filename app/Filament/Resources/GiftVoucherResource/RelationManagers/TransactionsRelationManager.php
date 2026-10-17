<?php

namespace App\Filament\Resources\GiftVoucherResource\RelationManagers;

use App\Filament\Resources\OrderResource;
use App\Models\GiftVoucherTransaction;
use App\Support\Money;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class TransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactions';

    protected static ?string $title = 'Verlauf (Ausgabe und Einlösungen)';

    protected static ?string $modelLabel = 'Buchung';

    protected static ?string $pluralModelLabel = 'Buchungen';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('order:id,order_number'))
            ->recordUrl(fn (GiftVoucherTransaction $r) => $r->order ? OrderResource::getUrl('view', ['record' => $r->order]) : null)
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->label('Zeit')->dateTime('d.m.Y H:i'),
                Tables\Columns\TextColumn::make('type')->label('Art')->badge()->formatStateUsing(fn (GiftVoucherTransaction $r) => $r->typeLabel())
                    ->color(fn (string $state) => match ($state) {
                        'redeem' => 'warning', 'issue', 'release' => 'success', 'cancel' => 'danger', default => 'gray'
                    }),
                Tables\Columns\TextColumn::make('amount')->label('Betrag')->alignEnd()
                    ->formatStateUsing(fn (int $state) => ($state > 0 ? '+' : ($state < 0 ? '−' : '')).Money::format(abs($state))),
                Tables\Columns\TextColumn::make('balance_after')->label('Saldo')->alignEnd()->formatStateUsing(fn (int $state) => Money::format($state)),
                Tables\Columns\TextColumn::make('order.order_number')->label('Bestellung')->placeholder('—'),
                Tables\Columns\TextColumn::make('note')->label('Vermerk')->placeholder('—')->wrap(),
            ])
            ->headerActions([])->actions([])->bulkActions([]);
    }
}
