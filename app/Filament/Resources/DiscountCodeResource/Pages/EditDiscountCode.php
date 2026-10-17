<?php

namespace App\Filament\Resources\DiscountCodeResource\Pages;

use App\Filament\Resources\DiscountCodeResource;
use App\Models\DiscountCode;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDiscountCode extends EditRecord
{
    protected static string $resource = DiscountCodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Eingelöste Codes nicht löschen (Nachweis in Bestellungen), stattdessen deaktivieren
            Actions\DeleteAction::make()->visible(fn (DiscountCode $record) => ! $record->redemptions()->exists()),
        ];
    }
}
