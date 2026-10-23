<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var User $user */
        $user = $this->getRecord();
        $data['is_staff'] = (bool) $user->is_staff;
        $data['is_active'] = (bool) $user->is_active;
        $data['email_verified'] = $user->email_verified_at !== null;

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var User $record */
        $record->fill(Arr::except($data, UserResource::PROTECTED_FIELDS));
        $record->is_active = (bool) ($data['is_active'] ?? $record->is_active);
        if (UserResource::isSuperAdmin() && array_key_exists('is_staff', $data)) {
            $record->is_staff = (bool) $data['is_staff'];
        }
        if (array_key_exists('email_verified', $data)) {
            $record->email_verified_at = $data['email_verified'] ? ($record->email_verified_at ?? now()) : null;
        }
        $record->save();

        return $record;
    }

    protected function afterSave(): void
    {
        // Rollen ohne Mitarbeiterstatus ergeben keinen Sinn
        /** @var User $user */
        $user = $this->getRecord();
        if (! $user->is_staff && UserResource::isSuperAdmin()) {
            $user->syncRoles([]);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()->label('Konto löschen')
                ->modalDescription('Das Konto wird deaktiviert (Soft Delete). Bestellungen und Dokumente bleiben für die Aufbewahrungsfristen erhalten.'),
            Actions\RestoreAction::make(),
        ];
    }
}
