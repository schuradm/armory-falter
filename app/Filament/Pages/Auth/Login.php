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
        if (filament()->auth()->check()) {
            $this->redirect(filament()->getUrl());

            return;
        }

        // Nicht filament()->getUrl(): für Gäste liefert das die Login-Seite selbst (Schleife zur Shop-Startseite).
        // Ein vom Authenticate-Middleware gemerkter Backend-Deeplink (z. B. eine Bestellung) bleibt erhalten.
        $panelUrl = url(filament()->getCurrentPanel()->getPath());
        if (! str_starts_with((string) session('url.intended'), $panelUrl) || str_starts_with((string) session('url.intended'), filament()->getLoginUrl())) {
            session()->put('url.intended', $panelUrl);
        }
        $this->redirect(route('login'));
    }
}
