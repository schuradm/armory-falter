@php(app(\App\Support\Seo::class)->title('Neues Passwort')->noindex())
<x-auth-card title="Neues Passwort">
    <form method="POST" action="{{ route('password.update') }}" class="space-y-4">@csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        <div><label class="label" for="email">E-Mail</label><input id="email" type="email" name="email" value="{{ old('email', $request->email) }}" class="input" required></div>
        <div><label class="label" for="pw">Neues Passwort</label><input id="pw" type="password" name="password" class="input" autocomplete="new-password" required></div>
        <div><label class="label" for="pw2">Wiederholen</label><input id="pw2" type="password" name="password_confirmation" class="input" autocomplete="new-password" required></div>
        <button class="btn-primary w-full">Passwort speichern</button>
    </form>
</x-auth-card>
