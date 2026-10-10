@php(app(\App\Support\Seo::class)->title('Zwei-Faktor-Anmeldung')->noindex())
<x-auth-card title="Bestätigungscode" subtitle="Geben Sie den Code aus Ihrer Authenticator-App ein.">
    <form method="POST" action="{{ route('two-factor.login.store') }}" class="space-y-4" x-data="{ recovery: false }">@csrf
        <div x-show="!recovery"><label class="label" for="code">Code</label><input id="code" name="code" inputmode="numeric" autocomplete="one-time-code" class="input" autofocus></div>
        <div x-show="recovery" x-cloak><label class="label" for="rc">Wiederherstellungscode</label><input id="rc" name="recovery_code" class="input"></div>
        <button class="btn-primary w-full">Anmelden</button>
        <button type="button" class="w-full text-sm text-accent" @click="recovery = !recovery" x-text="recovery ? 'Authenticator-Code verwenden' : 'Wiederherstellungscode verwenden'"></button>
    </form>
</x-auth-card>
