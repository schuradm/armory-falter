<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\Login as BaseLogin;

/**
 * Das Backend nutzt die Shop-Anmeldung (Fortify) – nur dort gibt es die Zwei-Faktor-Abfrage.
 * Nach erfolgreicher Anmeldung geht es zurück zu /kms-login.
 */
class Login extends BaseLogin
{
    public function mount(): void
    {
        session()->put('url.intended', filament()->getUrl());
        $this->redirect(route('login'));
    }
}
