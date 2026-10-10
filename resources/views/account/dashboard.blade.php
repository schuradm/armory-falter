<x-layouts.account active="dashboard">
    <h1 class="font-display text-2xl font-extrabold uppercase">Willkommen, {{ $user->first_name }}</h1>
    @unless ($user->hasVerifiedEmail())
        <div class="card mt-6 border-amber-500/40 p-4 text-sm">Bitte bestätigen Sie Ihre E-Mail-Adresse. <form method="POST" action="{{ route('verification.send') }}" class="inline">@csrf<button class="text-accent underline">Link erneut senden</button></form></div>
    @endunless
    <div class="mt-6 grid gap-4 md:grid-cols-3">
        <a href="{{ route('account.documents') }}" class="card p-5 hover:border-accent">
            <p class="label">Altersverifikation</p>
            <p class="font-display text-lg font-bold">{{ $user->age_verified_at ? 'Bestätigt' : 'Offen' }}</p>
            @if ($openDocs)<p class="mt-1 text-xs text-amber-400">{{ $openDocs }} Dokument(e) in Prüfung</p>@endif
        </a>
        <a href="{{ route('account.documents') }}" class="card p-5 hover:border-accent">
            <p class="label">Erwerbsberechtigungen</p>
            <p class="font-display text-lg font-bold">{{ $user->activeEntitlements->count() }} aktiv</p>
            <p class="mt-1 text-xs text-muted">WBK, Jagdschein, Munition</p>
        </a>
        <a href="{{ route('account.security') }}" class="card p-5 hover:border-accent">
            <p class="label">Zwei-Faktor-Anmeldung</p>
            <p class="font-display text-lg font-bold">{{ $user->two_factor_confirmed_at ? 'Aktiv' : 'Nicht aktiv' }}</p>
            @unless ($user->two_factor_confirmed_at)<p class="mt-1 text-xs text-accent">Jetzt absichern</p>@endunless
        </a>
    </div>
    <h2 class="mt-10 font-display text-lg font-bold">Letzte Bestellungen</h2>
    @include('account.partials.order-table', ['orders' => $orders])
</x-layouts.account>
