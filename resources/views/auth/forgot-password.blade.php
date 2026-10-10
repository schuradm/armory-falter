@php(app(\App\Support\Seo::class)->title('Passwort vergessen')->noindex())
<x-auth-card title="Passwort vergessen" subtitle="Wir senden Ihnen einen Link zum Zurücksetzen.">
    @if (session('status'))<p class="mb-4 text-sm text-emerald-400">{{ session('status') }}</p>@endif
    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">@csrf
        <div><label class="label" for="email">E-Mail</label><input id="email" type="email" name="email" value="{{ old('email') }}" class="input" required autofocus></div>
        <button class="btn-primary w-full">Link senden</button>
    </form>
</x-auth-card>
