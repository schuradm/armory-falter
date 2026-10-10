<x-layouts.app>
    <section class="fx-aurora relative bg-ink py-16 text-white sm:py-24" style="--af-fg:#fff;--af-muted:#c4c4c4">
        <div class="container-af relative">
            <x-breadcrumbs :items="app(\App\Support\Seo::class)->breadcrumbs"/>
            <p class="eyebrow mt-6">Erleben statt nur kaufen</p>
            <h1 class="mt-4 font-display text-4xl font-extrabold uppercase sm:text-6xl">Events & <span class="fx-brand-text">Workshops</span></h1>
            <p class="mt-5 max-w-2xl text-lg text-white/80">Shooting Day, Testtage, Jagdabende, Optik-Workshops und Wiederlade-Seminare. Treffen Sie Gleichgesinnte und testen Sie Neuheiten selbst.</p>
        </div>
    </section>
    <div class="container-af py-16">
        <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
            @forelse ($events as $event)
                <a href="{{ route('events.show', $event) }}" class="card fx-card group flex flex-col p-6">
                    <span class="badge self-start bg-brand text-white">{{ $event->starts_at->translatedFormat('D, d. F Y') }}</span>
                    <h2 class="mt-4 font-display text-xl font-bold group-hover:text-accent">{{ $event->title }}</h2>
                    <p class="mt-2 flex-1 text-sm text-muted">{{ $event->teaser }}</p>
                    <p class="mt-5 flex items-center justify-between border-t border-line pt-4 text-sm">
                        <span>{{ $event->price_gross ? \App\Support\Money::format($event->price_gross) : 'kostenlos' }}</span>
                        @if (($left = $event->seatsLeft()) !== null)<span class="{{ $left < 5 ? 'text-accent' : 'text-muted' }}">{{ $left > 0 ? "noch {$left} Plätze" : 'ausgebucht' }}</span>@endif
                    </p>
                </a>
            @empty
                <div class="card p-10 text-center md:col-span-3">
                    <p class="font-display text-xl font-bold">Neue Termine folgen in Kürze</p>
                    <p class="mt-2 text-muted">Tragen Sie sich in den Newsletter ein, dann verpassen Sie kein Event.</p>
                </div>
            @endforelse
        </div>
    </div>
</x-layouts.app>
