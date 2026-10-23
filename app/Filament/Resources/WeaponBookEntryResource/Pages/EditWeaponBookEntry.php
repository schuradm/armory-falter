<?php

namespace App\Filament\Resources\WeaponBookEntryResource\Pages;

use App\Filament\Resources\WeaponBookEntryResource;
use App\Models\WeaponBookEntry;
use App\Services\AuditLogger;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Arr;

/** Nur die NWR-Meldung ist nachträglich pflegbar (alle anderen Felder sind gesperrt und werden nicht gespeichert). */
class EditWeaponBookEntry extends EditRecord
{
    protected static string $resource = WeaponBookEntryResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return Arr::only($data, ['nwr_reported_at', 'nwr_reference']);
    }

    protected function afterSave(): void
    {
        /** @var WeaponBookEntry $entry */
        $entry = $this->record;
        app(AuditLogger::class)->log('weapon_book.nwr_reported', $entry->weaponItem, [], [
            'entry_id' => $entry->id, 'nwr_reference' => $entry->nwr_reference,
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
