<?php

namespace App\Filament\Resources\WeaponBookEntryResource\Pages;

use App\Filament\Resources\WeaponBookEntryResource;
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
        app(AuditLogger::class)->log('weapon_book.nwr_reported', $this->record->weaponItem, [], [
            'entry_id' => $this->record->id, 'nwr_reference' => $this->record->nwr_reference,
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
