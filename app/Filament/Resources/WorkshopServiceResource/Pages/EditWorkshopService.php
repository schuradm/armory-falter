<?php

namespace App\Filament\Resources\WorkshopServiceResource\Pages;

use App\Filament\Resources\WorkshopServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWorkshopService extends EditRecord
{
    protected static string $resource = WorkshopServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
