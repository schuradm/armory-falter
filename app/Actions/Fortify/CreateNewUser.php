<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Rules\Captcha;
use App\Rules\Honeypot;
use App\Services\AuditLogger;
use App\Services\Newsletter\NewsletterService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    public function create(array $input): User
    {
        Validator::make($input, [
            'customer_type' => ['required', Rule::in(['private', 'company', 'authority', 'dealer'])],
            'salutation' => ['required', Rule::in(['herr', 'frau', 'divers', 'none'])],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'company_name' => ['nullable', 'required_unless:customer_type,private', 'string', 'max:200'],
            'birth_date' => ['required', 'date', 'before:-16 years', 'after:1900-01-01'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['required', 'string', 'email:rfc,dns', 'max:255', Rule::unique(User::class)],
            'password' => $this->passwordRules(),
            'privacy' => ['accepted'],
            'website' => [new Honeypot],
            'cf-turnstile-response' => [new Captcha],
        ], [
            'birth_date.before' => 'Ein Kundenkonto ist ab 16 Jahren möglich. Altersbeschränkte Artikel erst ab 18 bzw. 21 Jahren.',
            'privacy.accepted' => 'Bitte bestätigen Sie die Datenschutzerklärung.',
        ])->validate();

        $user = User::create([
            'customer_type' => $input['customer_type'],
            'salutation' => $input['salutation'],
            'first_name' => $input['first_name'],
            'last_name' => $input['last_name'],
            'company_name' => $input['company_name'] ?? null,
            'birth_date' => $input['birth_date'],
            'phone' => $input['phone'] ?? null,
            'email' => $input['email'],
            'password' => $input['password'],
        ]);
        $user->assignRole('customer');

        if (! empty($input['newsletter'])) {
            app(NewsletterService::class)->subscribe($user->email, [], $user->first_name, 'registration', $user->id);
        }
        app(AuditLogger::class)->log('user.registered', $user);

        return $user;
    }
}
