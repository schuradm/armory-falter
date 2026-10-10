<?php

namespace App\Filament\Resources\CustomerDocumentResource\Pages;

use App\Filament\Resources\CustomerDocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCustomerDocument extends EditRecord
{
    protected static string $resource = CustomerDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
