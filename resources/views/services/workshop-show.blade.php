<x-layouts.app>
    <div class="container-af py-12">
        <x-breadcrumbs :items="app(\App\Support\Seo::class)->breadcrumbs"/>
        <div class="mt-6 grid gap-12 lg:grid-cols-[1fr_360px]">
            <article>
                <p class="eyebrow">Werkstatt</p>
                <h1 class="mt-3 font-display text-3xl font-extrabold uppercase sm:text-5xl">{{ $service->name }}</h1>
                <div class="brand-rule mt-4"></div>
                <p class="mt-6 text-lg text-muted">{{ $service->teaser }}</p>
                <div class="prose-af mt-8">{!! $service->description !!}</div>
            </article>
            <aside>
                <div class="card sticky top-28 p-6">
                    <p class="label">Preis</p>
                    <p class="font-display text-3xl font-extrabold">@if ($service->price_from_gross)<span class="text-base font-medium text-muted">ab</span> {{ \App\Support\Money::format($service->price_from_gross) }}@else auf Anfrage @endif</p>
                    <p class="mt-1 text-xs text-muted">inkl. MwSt., zzgl. Material nach Aufwand</p>
                    @if ($service->duration_minutes)<p class="mt-4 flex items-center gap-2 text-sm"><x-icon name="clock" class="size-4 text-accent"/> ca. {{ $service->duration_minutes }} Minuten Arbeitszeit</p>@endif
                    @if ($service->is_bookable)
                        <a href="{{ route('appointments.workshop', ['leistung' => $service->id]) }}" class="btn-primary fx-shine mt-6 w-full">Termin buchen</a>
                    @else
                        <a href="{{ route('contact') }}" class="btn-primary mt-6 w-full">Anfrage senden</a>
                    @endif
                    <a href="{{ route('workshop.index') }}" class="btn-ghost mt-2 w-full">Alle Leistungen</a>
                </div>
            </aside>
        </div>
    </div>
</x-layouts.app>
