<?php

namespace App\Filament\Resources\WeaponBookEntryResource\Pages;

use App\Filament\Resources\WeaponBookEntryResource;
use App\Models\WeaponBookEntry;
use App\Services\AuditLogger;
use Filament\Resources\Pages\CreateRecord;

class CreateWeaponBookEntry extends CreateRecord
{
    protected static string $resource = WeaponBookEntryResource::class;

    protected static bool $canCreateAnother = false;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['booked_by'] = auth()->id();

        return $data;
    }

    protected function afterCreate(): void
    {
        /** @var WeaponBookEntry $entry */
        $entry = $this->record;
        app(AuditLogger::class)->log('weapon_book.booked', $entry->weaponItem, [], [
            'entry_id' => $entry->id, 'direction' => $entry->direction,
        ]);
    }
}
