<x-layouts.app>
    <section class="relative overflow-hidden border-b border-line bg-ink text-white" style="--af-fg:#fff;--af-muted:#bdbdbd">
        <div class="fx-aurora absolute inset-0" aria-hidden="true"></div>
        <div class="container-af relative py-16 lg:py-24">
            <x-breadcrumbs :items="app(\App\Support\Seo::class)->breadcrumbs" class="text-white/60"/>
            <p class="eyebrow mt-6">Büchsenmacher-Werkstatt</p>
            <h1 class="mt-4 max-w-3xl font-display text-4xl leading-tight font-extrabold uppercase sm:text-5xl">Handwerk, das <span class="fx-brand-text">trifft.</span></h1>
            <p class="mt-6 max-w-2xl text-lg text-white/75">Reinigung, Pflege, Optikmontage, Einschießen, Reparaturen und Cerakote-Beschichtung. Sorgfältig dokumentiert, termingerecht zurück.</p>
            <a href="{{ route('appointments.workshop') }}" class="btn-primary fx-shine mt-8 px-7 py-4"><x-icon name="calendar" class="size-4"/> Termin online buchen</a>
        </div>
    </section>

    <section class="container-af py-20">
        <x-section-heading eyebrow="Leistungen" title="Unsere Werkstatt-Services"/>
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($services as $s)
                <a href="{{ route('workshop.show', $s) }}" class="card reveal fx-tilt fx-spotlight group flex flex-col p-7 hover:border-accent" style="transition-delay: {{ $loop->index * 60 }}ms">
                    <x-icon name="wrench" class="size-8 text-accent"/>
                    <h2 class="mt-5 font-display text-xl font-bold">{{ $s->name }}</h2>
                    <p class="mt-2 flex-1 text-sm text-muted">{{ $s->teaser }}</p>
                    <div class="mt-6 flex items-center justify-between border-t border-line pt-4 text-sm">
                        <span>@if ($s->price_from_gross)ab <strong class="text-fg">{{ \App\Support\Money::format($s->price_from_gross) }}</strong>@else Preis auf Anfrage @endif</span>
                        <span class="inline-flex items-center gap-1 text-accent">Details <x-icon name="arrow-right" class="size-4 transition group-hover:translate-x-1"/></span>
                    </div>
                </a>
            @empty
                <p class="text-muted">Leistungen werden in Kürze ergänzt.</p>
            @endforelse
        </div>
    </section>

    <section class="bg-surface py-16">
        <div class="container-af flex flex-col items-center justify-between gap-6 text-center md:flex-row md:text-left">
            <div><h2 class="font-display text-2xl font-extrabold uppercase">Waffe bringen, Termin sparen</h2><p class="mt-2 text-muted">Buchen Sie Ihren Abgabetermin online. Wir melden uns mit Kostenvoranschlag, bevor wir loslegen.</p></div>
            <a href="{{ route('appointments.workshop') }}" class="btn-primary px-7 py-4 shrink-0">Termin buchen</a>
        </div>
    </section>
</x-layouts.app>
