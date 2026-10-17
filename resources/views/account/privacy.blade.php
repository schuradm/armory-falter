<x-layouts.account active="privacy">
    <h1 class="font-display text-2xl font-extrabold uppercase">Datenschutz</h1>
    @if (session('status'))<p class="card mt-4 p-4 text-sm" role="status">{{ session('status') }}</p>@endif
    @php($optedOut = auth()->user()->review_requests_opted_out_at)
    <section class="card mt-6 p-6">
        <h2 class="font-display text-lg font-bold">Bewertungseinladungen</h2>
        <p class="mt-1 text-sm text-muted">Nach der Zustellung bitten wir Sie einmalig per E-Mail, die gekauften Artikel zu bewerten (§ 7 Abs. 3 UWG). Sie können dem jederzeit widersprechen.</p>
        <form method="POST" action="{{ route('account.privacy.review-invites') }}" class="mt-4">@csrf
            <input type="hidden" name="allow" value="{{ $optedOut ? 1 : 0 }}">
            <p class="text-sm">Status: <span class="font-medium">{{ $optedOut ? 'abbestellt seit '.$optedOut->format('d.m.Y') : 'aktiv' }}</span></p>
            <button class="btn-outline mt-3 py-2">{{ $optedOut ? 'Einladungen wieder erhalten' : 'Keine Bewertungseinladungen mehr' }}</button>
        </form>
    </section>
    <section class="card mt-6 p-6">
        <h2 class="font-display text-lg font-bold">Datenauskunft</h2>
        <p class="mt-1 text-sm text-muted">Laden Sie alle zu Ihrem Konto gespeicherten Daten als Datei herunter (Art. 15 und 20 DSGVO).</p>
        <a href="{{ route('account.privacy.export') }}" class="btn-outline mt-4 py-2"><x-icon name="download" class="size-4"/> Daten herunterladen</a>
    </section>
    <section class="card mt-6 border-red-500/30 p-6">
        <h2 class="font-display text-lg font-bold">Konto löschen</h2>
        <p class="mt-1 text-sm text-muted">Ihre Kontodaten werden gelöscht bzw. anonymisiert. Daten mit gesetzlicher Aufbewahrungspflicht (z. B. Rechnungen, Waffenhandelsbuch) bewahren wir bis zum Fristende gesperrt auf.</p>
        <form method="POST" action="{{ route('account.privacy.destroy') }}" class="mt-4 flex max-w-md gap-2" onsubmit="return confirm('Konto endgültig löschen?')">@csrf @method('DELETE')
            <input type="password" name="password" placeholder="Passwort zur Bestätigung" class="input" required>
            <button class="btn bg-red-600 text-white hover:bg-red-500">Löschen</button>
        </form>
        @error('password')<p class="mt-2 text-xs text-red-400">{{ $message }}</p>@enderror
    </section>
</x-layouts.account>
