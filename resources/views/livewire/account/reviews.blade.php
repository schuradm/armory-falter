<div>
    <h1 class="font-display text-2xl font-extrabold uppercase">Meine Bewertungen</h1>
    <p class="mt-1 text-sm text-muted">Bewerten können Sie Artikel, die Sie bei uns gekauft und erhalten haben. Jede Bewertung prüfen wir vor der Veröffentlichung; angezeigt wird Ihr Vorname mit dem Anfangsbuchstaben des Nachnamens.</p>

    <section class="mt-6">
        <h2 class="font-display text-lg font-bold">Artikel bewerten</h2>
        @forelse ($reviewable as $p)
            <div class="card mt-3 p-4" wire:key="rv-{{ $p->id }}">
                <div class="flex items-center gap-4">
                    <span class="grid size-14 shrink-0 place-items-center overflow-hidden rounded bg-surface-2">@if ($img = $p->primaryImage())<x-img :media="$img" size="thumb" sizes="56px" alt="" class="size-full object-contain p-1"/>@else<x-icon name="box" class="size-6 text-muted"/>@endif</span>
                    <p class="min-w-0 flex-1 font-medium">{{ $p->name }}</p>
                    @if ($selected?->id !== $p->id)
                        <button type="button" wire:click="select('{{ $p->slug }}')" class="btn-outline py-2 text-sm">Bewerten</button>
                    @endif
                </div>
                @if ($selected?->id === $p->id)
                    <form wire:submit="submit" class="mt-4 space-y-4 border-t border-line pt-4">
                        <fieldset x-data="{ r: $wire.entangle('rating'), h: 0 }">
                            <legend class="label">Ihre Bewertung</legend>
                            <div class="flex gap-1" @mouseleave="h = 0">
                                @foreach (range(1, 5) as $s)
                                    <label class="cursor-pointer" @mouseenter="h = {{ $s }}">
                                        <input type="radio" name="rating" value="{{ $s }}" x-model.number="r" class="peer sr-only">
                                        <span class="sr-only">{{ $s }} {{ $s === 1 ? 'Stern' : 'Sterne' }}</span>
                                        <svg viewBox="0 0 24 24" class="size-8 rounded peer-focus-visible:outline-2 peer-focus-visible:outline-accent" :class="(h || r) >= {{ $s }} ? 'text-accent' : 'text-muted/40'" aria-hidden="true"><path fill="currentColor" d="m12 2 3.1 6.3 6.9 1-5 4.9 1.2 6.8L12 17.8 5.8 21l1.2-6.8-5-4.9 6.9-1z"/></svg>
                                    </label>
                                @endforeach
                            </div>
                            @error('rating')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror
                        </fieldset>
                        <div><label for="rv-title" class="label">Überschrift (optional)</label><input id="rv-title" type="text" wire:model="title" maxlength="120" class="input"></div>
                        <div><label for="rv-body" class="label">Ihre Erfahrung (optional)</label><textarea id="rv-body" wire:model="body" rows="4" maxlength="3000" class="input" placeholder="Was hat Ihnen gefallen, was weniger? Bitte keine Namen oder Kontaktdaten."></textarea></div>
                        <p class="text-xs text-muted">Wir veröffentlichen positive wie negative Bewertungen. Abgelehnt werden nur Beiträge mit Rechtsverstößen (z. B. Beleidigungen), personenbezogenen Daten oder ohne Produktbezug. Für Bewertungen gibt es keine Gegenleistung.</p>
                        <div class="flex gap-3"><button type="submit" class="btn-primary" wire:loading.attr="disabled">Bewertung absenden</button><button type="button" wire:click="select(null)" class="btn-ghost">Abbrechen</button></div>
                    </form>
                @endif
            </div>
        @empty
            <p class="card mt-3 p-4 text-sm text-muted">Derzeit gibt es keine Artikel zum Bewerten. Nach der Zustellung Ihrer nächsten Bestellung erscheinen die Artikel hier.</p>
        @endforelse
    </section>

    <section class="mt-10">
        <h2 class="font-display text-lg font-bold">Abgegebene Bewertungen</h2>
        @forelse ($mine as $r)
            <article class="card mt-3 p-4" wire:key="my-{{ $r->id }}">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <a href="{{ $r->product ? route('product.show', $r->product) : '#' }}" class="font-medium hover:text-accent">{{ $r->product?->name }}</a>
                    <span @class(['badge', 'bg-emerald-500/15 text-emerald-400' => $r->status === 'approved', 'bg-amber-500/15 text-amber-400' => $r->status === 'pending', 'bg-red-500/15 text-red-400' => $r->status === 'rejected'])>{{ $r->statusLabel() }}</span>
                </div>
                <x-stars :rating="$r->rating" class="mt-2"/>
                @if ($r->title)<p class="mt-2 font-medium">{{ $r->title }}</p>@endif
                @if ($r->body)<p class="mt-1 text-sm text-fg/85">{{ $r->body }}</p>@endif
                @if ($r->status === 'rejected' && $r->rejection_reason)<p class="mt-2 text-xs text-red-400">Grund: {{ $r->rejection_reason }}</p>@endif
                @if ($r->reply)<p class="mt-3 rounded-md bg-surface-2 p-3 text-sm"><span class="font-medium">Antwort von Armory Falter:</span> {{ $r->reply }}</p>@endif
            </article>
        @empty
            <p class="mt-3 text-sm text-muted">Sie haben noch keine Bewertungen abgegeben.</p>
        @endforelse
    </section>
</div>
