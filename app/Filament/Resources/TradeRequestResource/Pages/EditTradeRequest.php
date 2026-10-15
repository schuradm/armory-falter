<?php

namespace App\Filament\Resources\TradeRequestResource\Pages;

use App\Filament\Resources\TradeRequestResource;
use App\Models\TradeRequest;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTradeRequest extends EditRecord
{
    protected static string $resource = TradeRequestResource::class;

    public function getTitle(): string
    {
        return 'Anfrage '.$this->getRecord()->reference;
    }

    protected function getHeaderActions(): array
    {
        return [
            TradeRequestResource::pdfAction(Actions\Action::class),
            Actions\Action::make('egunCsv')->label('egun-CSV')->icon('heroicon-o-table-cells')->color('gray')
                ->action(fn (TradeRequest $record) => TradeRequestResource::egunCsvResponse(collect([$record]))),
            Actions\DeleteAction::make(),
        ];
    }
}
