<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Enums\ComplianceStatus;
use App\Enums\OrderStatus;
use App\Filament\Resources\OrderResource;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Alle'),
            'compliance' => Tab::make('Zur Freigabe')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('compliance_status', ComplianceStatus::Pending)
                    ->whereNotIn('status', [OrderStatus::Cancelled, OrderStatus::Refunded])),
            'payment' => Tab::make('Wartet auf Zahlung')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', OrderStatus::PendingPayment)),
            'ship' => Tab::make('Zu versenden')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', [OrderStatus::Paid, OrderStatus::Processing])),
            'done' => Tab::make('Erledigt')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', [OrderStatus::Shipped, OrderStatus::Delivered, OrderStatus::Completed, OrderStatus::ReadyForPickup])),
        ];
    }
}
