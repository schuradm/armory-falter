@php(app(\App\Support\Seo::class)->title('Anmelden')->noindex())
<x-auth-card title="Anmelden" subtitle="Melden Sie sich mit Ihrem Kundenkonto an.">
    <form method="POST" action="{{ route('login.store') }}" class="space-y-4">@csrf
        <div><label class="label" for="email">E-Mail</label><input id="email" type="email" name="email" value="{{ old('email') }}" class="input" autocomplete="username" required autofocus></div>
        <div><label class="label" for="password">Passwort</label><input id="password" type="password" name="password" class="input" autocomplete="current-password" required></div>
        <div class="flex items-center justify-between text-sm"><label class="flex items-center gap-2"><input type="checkbox" name="remember" class="rounded border-line bg-surface-2 text-accent"> Angemeldet bleiben</label><a href="{{ route('password.request') }}" class="text-accent">Passwort vergessen?</a></div>
        <button class="btn-primary w-full">Anmelden</button>
    </form>
    <p class="mt-6 text-center text-sm text-muted">Neu bei Armory Falter? <a href="{{ route('register') }}" class="text-accent">Konto erstellen</a></p>
</x-auth-card>
