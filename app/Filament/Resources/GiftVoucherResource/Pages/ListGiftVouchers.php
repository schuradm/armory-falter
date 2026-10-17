<?php

namespace App\Filament\Resources\GiftVoucherResource\Pages;

use App\Filament\Resources\GiftVoucherResource;
use App\Filament\Resources\GiftVoucherResource\Widgets\VoucherStats;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGiftVouchers extends ListRecords
{
    protected static string $resource = GiftVoucherResource::class;

    protected function getHeaderWidgets(): array
    {
        return [VoucherStats::class];
    }

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label('Gutschein ausstellen')];
    }
}
