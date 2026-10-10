<?php

namespace App\Filament\Resources\LocalPageResource\Pages;

use App\Filament\Resources\LocalPageResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLocalPage extends EditRecord
{
    protected static string $resource = LocalPageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
