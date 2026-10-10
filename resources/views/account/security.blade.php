<x-layouts.account active="security">
    <h1 class="font-display text-2xl font-extrabold uppercase">Sicherheit</h1>
    @php($user = auth()->user())
    <section class="card mt-6 p-6">
        <h2 class="font-display text-lg font-bold">Zwei-Faktor-Anmeldung (2FA)</h2>
        <p class="mt-1 text-sm text-muted">Schützen Sie Ihr Konto mit einer Authenticator-App (z. B. Google Authenticator, Microsoft Authenticator, 1Password). Für Mitarbeiter ist 2FA Pflicht.</p>
        @if (! $user->two_factor_secret)
            <form method="POST" action="{{ route('two-factor.enable') }}" class="mt-4">@csrf<button class="btn-primary">2FA aktivieren</button></form>
        @elseif (! $user->two_factor_confirmed_at)
            <div class="mt-4 grid gap-6 md:grid-cols-[auto_1fr]">
                <div class="rounded-md bg-white p-3">{!! $user->twoFactorQrCodeSvg() !!}</div>
                <form method="POST" action="{{ route('two-factor.confirm') }}" class="space-y-3">@csrf
                    <p class="text-sm">Scannen Sie den QR-Code und geben Sie den 6-stelligen Code ein.</p>
                    <input name="code" inputmode="numeric" autocomplete="one-time-code" class="input max-w-40" placeholder="123456" required>
                    @error('code', 'confirmTwoFactorAuthentication')<p class="text-xs text-red-400">{{ $message }}</p>@enderror
                    <button class="btn-primary">Bestätigen</button>
                </form>
            </div>
        @else
            <p class="mt-4 flex items-center gap-2 text-emerald-400"><x-icon name="check" class="size-5"/> Aktiv</p>
            <details class="mt-4 text-sm"><summary class="cursor-pointer text-accent">Wiederherstellungscodes anzeigen</summary>
                <ul class="mt-2 grid grid-cols-2 gap-1 font-mono text-xs">@foreach ($user->recoveryCodes() as $code)<li>{{ $code }}</li>@endforeach</ul>
                <form method="POST" action="{{ route('two-factor.recovery-codes') }}" class="mt-2">@csrf<button class="text-xs text-accent">Neue Codes erzeugen</button></form>
            </details>
            <form method="POST" action="{{ route('two-factor.disable') }}" class="mt-4">@csrf @method('DELETE')<button class="btn-outline py-2" @disabled($user->is_staff)>2FA deaktivieren</button></form>
        @endif
    </section>
    <section class="card mt-6 p-6">
        <h2 class="font-display text-lg font-bold">Passwort ändern</h2>
        <form method="POST" action="{{ route('user-password.update') }}" class="mt-4 grid max-w-md gap-3">@csrf @method('PUT')
            <input type="password" name="current_password" placeholder="Aktuelles Passwort" class="input" autocomplete="current-password" required>
            <input type="password" name="password" placeholder="Neues Passwort (mind. 10 Zeichen)" class="input" autocomplete="new-password" required>
            <input type="password" name="password_confirmation" placeholder="Neues Passwort wiederholen" class="input" autocomplete="new-password" required>
            @if ($errors->updatePassword->any())<p class="text-xs text-red-400">{{ $errors->updatePassword->first() }}</p>@endif
            @if (session('status') === 'password-updated')<p class="text-xs text-emerald-400">Passwort geändert.</p>@endif
            <button class="btn-primary">Passwort ändern</button>
        </form>
    </section>
</x-layouts.account>
