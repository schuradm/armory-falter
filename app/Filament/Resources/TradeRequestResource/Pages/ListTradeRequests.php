<?php

namespace App\Filament\Resources\TradeRequestResource\Pages;

use App\Enums\TradeRequestType;
use App\Filament\Resources\TradeRequestResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListTradeRequests extends ListRecords
{
    protected static string $resource = TradeRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Anfrage erfassen'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Alle'),
            'consignment' => Tab::make('Verkaufsservice (egun)')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('type', TradeRequestType::Consignment)),
            'buyback' => Tab::make('Ankauf')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('type', TradeRequestType::Buyback)),
        ];
    }
}
