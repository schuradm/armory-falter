{{-- Widerrufsbutton: Schritt 1 Angaben („Vertrag widerrufen“), Schritt 2 Prüfen („Widerruf bestätigen“) --}}
<x-layouts.app>
    <div class="container-af max-w-3xl py-12">
        <x-breadcrumbs :items="app(\App\Support\Seo::class)->breadcrumbs"/>
        <h1 class="mt-4 font-display text-3xl font-extrabold uppercase sm:text-4xl">{{ $step === 'review' ? 'Widerruf bestätigen' : 'Vertrag widerrufen' }}</h1>
        <div class="brand-rule mt-4"></div>

        @if ($step === 'form')
            <p class="mt-6 text-muted">Hier können Sie einen bei uns geschlossenen Kaufvertrag innerhalb der Widerrufsfrist von 14 Tagen widerrufen, ohne Angabe von Gründen. Im nächsten Schritt prüfen Sie Ihre Angaben und bestätigen den Widerruf. Sie erhalten sofort eine Eingangsbestätigung per E-Mail. Einzelheiten in der <a href="{{ route('page.show', 'widerruf') }}" class="text-accent underline">Widerrufsbelehrung</a>.</p>

            <form method="post" action="{{ route('withdrawal.review') }}" class="card mt-8 grid gap-5 p-6 sm:grid-cols-2">
                @csrf
                <div class="sm:col-span-2"><label class="label" for="wd-name">Vor- und Nachname *</label>
                    <input id="wd-name" name="name" value="{{ old('name', $draft['name'] ?? '') }}" class="input" required autocomplete="name">
                    @error('name')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror</div>
                <div><label class="label" for="wd-email">E-Mail für die Bestätigung *</label>
                    <input id="wd-email" name="email" type="email" value="{{ old('email', $draft['email'] ?? '') }}" class="input" required autocomplete="email">
                    @error('email')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror</div>
                <div><label class="label" for="wd-order">Bestellnummer *</label>
                    @if ($orders->isNotEmpty())
                        <select id="wd-order" name="order_number" class="input" required>
                            @foreach ($orders as $o)<option value="{{ $o->order_number }}" @selected(old('order_number', $draft['order_number'] ?? '') === $o->order_number)>{{ $o->order_number }} vom {{ $o->placed_at->format('d.m.Y') }} ({{ \App\Support\Money::format($o->total_gross) }})</option>@endforeach
                        </select>
                    @else
                        <input id="wd-order" name="order_number" value="{{ old('order_number', $draft['order_number'] ?? '') }}" class="input" required placeholder="z. B. AF-2026-000123">
                    @endif
                    @error('order_number')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror</div>
                <div class="sm:col-span-2"><label class="label" for="wd-items">Welche Artikel? (leer lassen = gesamte Bestellung)</label>
                    <textarea id="wd-items" name="items" rows="3" class="input">{{ old('items', $draft['items'] ?? '') }}</textarea></div>
                <div class="sm:col-span-2"><label class="label" for="wd-msg">Nachricht an uns (optional)</label>
                    <textarea id="wd-msg" name="message" rows="3" class="input">{{ old('message', $draft['message'] ?? '') }}</textarea></div>
                <input type="text" name="website" class="hidden" tabindex="-1" autocomplete="off" aria-hidden="true">
                <div class="sm:col-span-2"><button type="submit" class="btn-primary w-full sm:w-auto">Weiter zur Bestätigung</button></div>
            </form>
        @else
            <p class="mt-6 text-muted">Bitte prüfen Sie Ihre Angaben. Mit „Widerruf bestätigen“ erklären Sie den Widerruf verbindlich.</p>
            <dl class="card mt-8 grid gap-x-6 gap-y-3 p-6 text-sm sm:grid-cols-[180px_1fr]">
                <dt class="text-muted">Name</dt><dd>{{ $draft['name'] }}</dd>
                <dt class="text-muted">E-Mail</dt><dd>{{ $draft['email'] }}</dd>
                <dt class="text-muted">Bestellnummer</dt><dd>{{ $draft['order_number'] }}</dd>
                <dt class="text-muted">Artikel</dt><dd>{{ ($draft['items'] ?? null) ?: 'gesamte Bestellung' }}</dd>
                @if (! empty($draft['message']))<dt class="text-muted">Nachricht</dt><dd>{{ $draft['message'] }}</dd>@endif
            </dl>
            <p class="mt-6 font-medium">Erklärung: Hiermit widerrufe ich den oben genannten Vertrag{{ ! empty($draft['items']) ? ' über die angegebenen Artikel' : '' }}.</p>
            <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                <form method="post" action="{{ route('withdrawal.store') }}">@csrf<button type="submit" class="btn-primary w-full sm:w-auto">Widerruf bestätigen</button></form>
                <a href="{{ route('withdrawal.create') }}" class="btn-outline">Angaben ändern</a>
            </div>
        @endif
    </div>
</x-layouts.app>
