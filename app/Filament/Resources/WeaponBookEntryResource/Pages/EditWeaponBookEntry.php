<?php

namespace App\Filament\Resources\WeaponBookEntryResource\Pages;

use App\Filament\Resources\WeaponBookEntryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWeaponBookEntry extends EditRecord
{
    protected static string $resource = WeaponBookEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
