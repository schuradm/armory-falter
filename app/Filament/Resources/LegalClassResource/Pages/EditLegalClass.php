<?php

namespace App\Filament\Resources\LegalClassResource\Pages;

use App\Filament\Resources\LegalClassResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLegalClass extends EditRecord
{
    protected static string $resource = LegalClassResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
