<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LatestOrders extends TableWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Neueste Bestellungen';

    public function table(Table $table): Table
    {
        return $table
            ->query(Order::query()->with('user:id,first_name,last_name,company_name')->latest('placed_at')->limit(10))
            ->paginated(false)
            ->recordUrl(function (Order $record) {
                foreach (['view', 'edit'] as $page) {
                    if (OrderResource::hasPage($page)) {
                        return OrderResource::getUrl($page, ['record' => $record]);
                    }
                }

                return null;
            })
            ->columns([
                Tables\Columns\TextColumn::make('order_number')->label('Nummer')->fontFamily('mono'),
                Tables\Columns\TextColumn::make('placed_at')->label('Datum')->dateTime('d.m.Y H:i'),
                Tables\Columns\TextColumn::make('customer')->label('Kunde')
                    ->getStateUsing(fn (Order $r) => $r->user?->company_name ?: trim(($r->user?->first_name ?? '').' '.($r->user?->last_name ?? ''))),
                Tables\Columns\TextColumn::make('total_gross')->label('Summe')->formatStateUsing(fn ($state) => \App\Support\Money::format((int) $state)),
                Tables\Columns\TextColumn::make('payment_method_code')->label('Zahlart'),
                Tables\Columns\TextColumn::make('status')->label('Status')->badge(),
                Tables\Columns\TextColumn::make('compliance_status')->label('Freigabe')->badge()
                    ->color(fn ($state) => match ($state?->value) { 'pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger', default => 'gray' }),
            ])
            ->emptyStateHeading('Noch keine Bestellungen');
    }
}
