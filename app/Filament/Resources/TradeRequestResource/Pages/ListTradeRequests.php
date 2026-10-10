<?php

namespace App\Filament\Resources\TradeRequestResource\Pages;

use App\Filament\Resources\TradeRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTradeRequests extends ListRecords
{
    protected static string $resource = TradeRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
