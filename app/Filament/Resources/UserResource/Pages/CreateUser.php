<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $user = new User(Arr::except($data, UserResource::PROTECTED_FIELDS));
        $user->forceFill([
            'is_active' => (bool) ($data['is_active'] ?? true),
            'is_staff' => UserResource::isSuperAdmin() && (bool) ($data['is_staff'] ?? false),
            'email_verified_at' => ($data['email_verified'] ?? true) ? now() : null,
        ])->save();

        return $user;
    }
}
