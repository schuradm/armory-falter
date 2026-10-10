@php(app(\App\Support\Seo::class)->title('Passwort bestätigen')->noindex())
<x-auth-card title="Passwort bestätigen" subtitle="Bitte bestätigen Sie diesen Schritt mit Ihrem Passwort.">
    <form method="POST" action="{{ route('password.confirm.store') }}" class="space-y-4">@csrf
        <input type="password" name="password" class="input" autocomplete="current-password" required autofocus aria-label="Passwort">
        <button class="btn-primary w-full">Bestätigen</button>
    </form>
</x-auth-card>
