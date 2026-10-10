@php(app(\App\Support\Seo::class)->title('Kundenkonto erstellen')->noindex())
<x-auth-card title="Konto erstellen" subtitle="Für den Kauf altersbeschränkter und erlaubnispflichtiger Artikel benötigen Sie ein Kundenkonto.">
    <form method="POST" action="{{ route('register.store') }}" class="grid gap-4 sm:grid-cols-2">@csrf
        <div class="sm:col-span-2"><label class="label" for="ct">Kundenart</label>
            <select id="ct" name="customer_type" class="input">@foreach (['private' => 'Privatkunde', 'company' => 'Unternehmen / Sicherheitsdienst', 'authority' => 'Behörde', 'dealer' => 'Waffenhändler'] as $k => $v)<option value="{{ $k }}" @selected(old('customer_type') === $k)>{{ $v }}</option>@endforeach</select></div>
        <div class="sm:col-span-2"><label class="label" for="cn">Firma / Behörde</label><input id="cn" name="company_name" value="{{ old('company_name') }}" class="input"></div>
        <div><label class="label" for="sal">Anrede</label><select id="sal" name="salutation" class="input"><option value="herr">Herr</option><option value="frau">Frau</option><option value="divers">Divers</option><option value="none">Keine Angabe</option></select></div>
        <div><label class="label" for="bd">Geburtsdatum *</label><input id="bd" type="date" name="birth_date" value="{{ old('birth_date') }}" class="input" required></div>
        <div><label class="label" for="fn">Vorname *</label><input id="fn" name="first_name" value="{{ old('first_name') }}" class="input" autocomplete="given-name" required></div>
        <div><label class="label" for="ln">Nachname *</label><input id="ln" name="last_name" value="{{ old('last_name') }}" class="input" autocomplete="family-name" required></div>
        <div class="sm:col-span-2"><label class="label" for="em">E-Mail *</label><input id="em" type="email" name="email" value="{{ old('email') }}" class="input" autocomplete="email" required></div>
        <div class="sm:col-span-2"><label class="label" for="ph">Telefon</label><input id="ph" name="phone" value="{{ old('phone') }}" class="input" autocomplete="tel"></div>
        <div><label class="label" for="pw">Passwort *</label><input id="pw" type="password" name="password" class="input" autocomplete="new-password" required></div>
        <div><label class="label" for="pw2">Wiederholen *</label><input id="pw2" type="password" name="password_confirmation" class="input" autocomplete="new-password" required></div>
        <p class="text-xs text-muted sm:col-span-2">Mindestens 10 Zeichen mit Groß- und Kleinbuchstaben und Zahl.</p>
        <label class="flex items-start gap-2 text-sm sm:col-span-2"><input type="checkbox" name="privacy" value="1" class="mt-0.5 rounded border-line bg-surface-2 text-accent" required> <span>Ich habe die <a href="{{ route('page.show', 'datenschutz') }}" class="text-accent underline" target="_blank">Datenschutzerklärung</a> gelesen.</span></label>
        <label class="flex items-start gap-2 text-sm sm:col-span-2"><input type="checkbox" name="newsletter" value="1" class="mt-0.5 rounded border-line bg-surface-2 text-accent"> <span>Newsletter abonnieren (Bestätigung per E-Mail, jederzeit abbestellbar).</span></label>
        <div class="sm:col-span-2"><x-captcha/></div>
        <button class="btn-primary sm:col-span-2">Konto erstellen</button>
    </form>
    <p class="mt-6 text-center text-sm text-muted">Schon registriert? <a href="{{ route('login') }}" class="text-accent">Anmelden</a></p>
</x-auth-card>
