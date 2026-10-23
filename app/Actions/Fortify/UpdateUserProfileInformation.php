<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;

class UpdateUserProfileInformation implements UpdatesUserProfileInformation
{
    /**
     * Geburtsdatum ist nach einer Altersverifikation gesperrt und nur durch das Team änderbar.
     */
    public function update(User $user, array $input): void
    {
        Validator::make($input, [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'company_name' => ['nullable', 'string', 'max:200'],
            'phone' => ['nullable', 'string', 'max:40'],
            'birth_date' => [$user->age_verified_at ? 'prohibited' : 'nullable', 'date', 'before:-16 years'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
        ])->validateWithBag('updateProfileInformation');

        $data = collect($input)->only(['first_name', 'last_name', 'company_name', 'phone', 'birth_date'])->filter(fn ($v) => $v !== null)->all();

        if ($input['email'] !== $user->email) {
            $user->forceFill($data + ['email' => $input['email'], 'email_verified_at' => null])->save();
            $user->sendEmailVerificationNotification();
        } else {
            $user->forceFill($data)->save();
        }
    }
}
